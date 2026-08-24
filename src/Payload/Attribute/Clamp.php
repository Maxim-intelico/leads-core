<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Attribute;

/**
 * Ограничение целого значения диапазоном [min, max] вместо отклонения.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class Clamp
{
    public function __construct(
        public int $min,
        public int $max,
    ) {
    }
}
