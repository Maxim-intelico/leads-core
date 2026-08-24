<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class FromBody
{
    /**
     * @param ?string $key ключ в теле; null — имя свойства
     */
    public function __construct(
        public ?string $key = null,
    ) {
    }
}
