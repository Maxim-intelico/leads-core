<?php

declare(strict_types=1);

namespace Leads\Core\Payload;

/**
 * Сырое значение из источника + текущая глубина вложенности.
 *
 * Глубина — вторая линия обороны от программной ошибки; защита от DoS живёт
 * в RequestContext (json_validate с лимитом глубины до построения массива).
 */
final readonly class RawValue
{
    private function __construct(
        public mixed $value,
        public int $depth,
    ) {
    }

    public static function of(mixed $value, int $depth = 0): self
    {
        return new self($value, $depth);
    }
}
