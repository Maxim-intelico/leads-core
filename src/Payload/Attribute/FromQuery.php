<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class FromQuery
{
    /**
     * @param ?string $key имя query-параметра; null — имя свойства
     */
    public function __construct(
        public ?string $key = null,
    ) {
    }
}
