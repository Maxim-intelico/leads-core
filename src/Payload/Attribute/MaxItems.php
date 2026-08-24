<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Attribute;

/**
 * Переопределение лимита элементов коллекции (по умолчанию 1000).
 * Это ограничитель нагрузки, а не политика прощения формата: превышение — всегда 400.
 * На #[FreeForm] не действует по дизайну — там границы задают лимиты байт и глубины тела.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class MaxItems
{
    public function __construct(
        public int $max,
    ) {
    }
}
