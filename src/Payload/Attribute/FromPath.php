<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class FromPath
{
    /**
     * @param ?string $key имя параметра роута; null — имя свойства
     */
    public function __construct(
        public ?string $key = null,
    ) {
    }
}
