<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Resolver;

use Leads\Core\Payload\Attribute\Payload;
use Leads\Core\Payload\PayloadMapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Находит #[Payload] на аргументе контроллера — больше ничего не делает.
 * Регистрируется автоконфигурацией (controller.argument_value_resolver), YAML не нужен.
 */
final readonly class PayloadValueResolver implements ValueResolverInterface
{
    public function __construct(
        private PayloadMapper $mapper,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $attributes = $argument->getAttributesOfType(Payload::class, ArgumentMetadata::IS_INSTANCEOF);

        if ($attributes === []) {
            return [];
        }

        $type = $argument->getType();

        if ($type === null || class_exists($type) === false) {
            throw new \LogicException(sprintf(
                'Argument $%s with #[Payload] must be typed with a request DTO class.',
                $argument->getName(),
            ));
        }

        return [$this->mapper->map($type, $request, $attributes[0]->groups)];
    }
}
