<?php

declare(strict_types=1);

namespace Leads\Core\Payload;

use Leads\Core\Payload\Error\Reason;
use Leads\Core\Payload\Plan\PayloadPlan;

/**
 * Сборка объекта из массива — рекурсивно и без знания о HTTP: NestedObjectCaster
 * вызывает того же ассемблера с планом дочернего класса. Разница между корнем и
 * вложенностью ровно одна: у корня значения собраны из источников, у вложенного они уже в массиве.
 *
 * Единственный класс модуля, где допустимы @psalm-suppress: динамическая сборка
 * из array<string, mixed> принципиально не типизируется. Гарантию даёт конструктор DTO —
 * его параметры типизированы, и сюда доходят только значения, прошедшие кастеры.
 */
final class ObjectAssembler
{
    /**
     * Вторая линия обороны от программной ошибки; защита от DoS — json_validate
     * с лимитом глубины в RequestContext, до построения массива.
     */
    private const int MAX_DEPTH = 32;

    /**
     * @param array<array-key, mixed> $data
     *
     * @return Result<object>
     */
    public function assemble(PayloadPlan $plan, array $data, int $depth = 0): Result
    {
        if ($depth > self::MAX_DEPTH) {
            return Result::fail(new Reason('depth.exceeded', 'Payload is nested too deeply.'));
        }

        $args = [];
        $reasons = [];

        foreach ($plan->properties as $property) {
            if (\array_key_exists($property->key, $data) === false) {
                // «Параметр не передан» и null — разные вещи: absent проверяется до кастера.
                if ($property->hasDefault === false) {
                    $reasons[] = new Reason('required', 'Required.', path: $property->pointer);
                }

                continue;
            }

            $chain = $property->casterChain ?? throw new \LogicException(
                sprintf('Plan for %s::$%s has no caster chain.', $plan->class, $property->name),
            );

            $result = $chain->cast(RawValue::of($data[$property->key], $depth), $property);

            if ($result->isOk()) {
                $args[$property->name] = $result->value();

                continue;
            }

            foreach ($result->reasons() as $reason) {
                $reasons[] = $reason->nest($property->pointer);
            }

            break;   // fail-fast; убрать этот break = включить накопление ошибок
        }

        if ($reasons !== []) {
            return Result::fail(...$reasons);
        }

        /** @psalm-suppress MixedMethodCall динамический new из плана — см. док-блок класса */
        return Result::ok(new ($plan->class)(...$args));
    }
}
