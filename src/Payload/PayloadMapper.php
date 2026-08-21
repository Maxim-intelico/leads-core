<?php

declare(strict_types=1);

namespace Leads\Core\Payload;

use Leads\Core\Payload\Error\PayloadViolationException;
use Leads\Core\Payload\Error\PropertyPathConverter;
use Leads\Core\Payload\Error\Violation;
use Leads\Core\Payload\Error\ViolationList;
use Leads\Core\Payload\Plan\PayloadPlan;
use Leads\Core\Payload\Source\ValueSource;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Один маппер на весь проект. Здесь нет ни одного условия про HTTP-метод,
 * режим чтения/записи или конкретный DTO: всё поведение выражено планом,
 * скомпилированным из атрибутов DTO.
 */
final class PayloadMapper
{
    /** @var array<string, ValueSource>|null */
    private ?array $sourceMap = null;

    /**
     * @param iterable<string, ValueSource> $sources tagged_iterator payload.source, индекс — ValueSource::key()
     */
    public function __construct(
        private readonly Plan\PlanFactory $plans,
        private readonly ObjectAssembler $assembler,
        private readonly iterable $sources,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     * @param ?list<string> $groups
     *
     * @return T
     */
    public function map(string $class, Request $request, ?array $groups = null): object
    {
        $plan = $this->plans->build($class);
        $context = RequestContext::fromRequest($request, $plan->maxBodyBytes);

        // Учёт до извлечения: на этой стадии ещё видно исходное тело целиком.
        $this->logUnknownBodyFields($plan, $context);

        $data = [];
        foreach ($plan->properties as $property) {
            $source = $this->source($property->sourceKey);
            if ($source->has($context, $property)) {
                $data[$property->key] = $source->extract($context, $property);
            }
        }

        $result = $this->assembler->assemble($plan, $data);

        if ($result->isOk() === false) {
            throw PayloadViolationException::fromCast(ViolationList::fromReasons(...$result->reasons()));
        }

        /** @var T $dto */
        $dto = $result->value();

        $this->validate($plan, $dto, $groups);

        return $dto;
    }

    /**
     * @param ?list<string> $groups
     */
    private function validate(PayloadPlan $plan, object $dto, ?array $groups): void
    {
        $violations = $this->validator->validate($dto, groups: $groups ?? $plan->groups);

        // Fail-fast и на стадии валидатора: берётся первая ошибка, остальные отбрасываются.
        foreach ($violations as $violation) {
            throw PayloadViolationException::fromValidator(new ViolationList([
                new Violation(
                    $this->pointerFor($plan, $violation->getPropertyPath()),
                    (string)$violation->getMessage(),
                    'validation.failed',
                ),
            ]));
        }
    }

    /**
     * Валидатор отдаёт 'items[0].quantity' — приводим к 'body/items/0/quantity':
     * первый сегмент маппится через pointerMap плана, остальное конвертируется по нотации.
     */
    private function pointerFor(PayloadPlan $plan, string $propertyPath): string
    {
        $pointer = PropertyPathConverter::toPointer($propertyPath);
        $segments = explode('/', $pointer, 2);
        $root = $plan->pointerMap[$segments[0]] ?? $segments[0];

        return isset($segments[1]) ? $root . '/' . $segments[1] : $root;
    }

    private function logUnknownBodyFields(PayloadPlan $plan, RequestContext $context): void
    {
        $unknown = array_diff(array_map(strval(...), array_keys($context->body())), $plan->bodyKeys);

        if ($unknown === []) {
            return;
        }

        // Только имена, никогда значения: в неизвестном поле может оказаться пароль или токен.
        // Query не логируем: utm_source и прочий шум внешнего мира забьёт выборку.
        $this->logger->info('payload.unknown_fields', [
            'dto' => $plan->class,
            'route' => $context->routeName(),
            'fields' => array_values($unknown),
        ]);
    }

    private function source(string $key): ValueSource
    {
        if ($this->sourceMap === null) {
            $this->sourceMap = [];
            foreach ($this->sources as $sourceKey => $source) {
                $this->sourceMap[$sourceKey] = $source;
            }
        }

        return $this->sourceMap[$key]
            ?? throw new \LogicException(sprintf('Unknown value source "%s".', $key));
    }
}
