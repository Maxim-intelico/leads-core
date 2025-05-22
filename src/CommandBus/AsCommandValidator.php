<?php

declare(strict_types=1);

namespace Leads\Core\CommandBus;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AsCommandValidator
{
    public function __construct(
        public string $commandClass,
    ) {
    }
}
