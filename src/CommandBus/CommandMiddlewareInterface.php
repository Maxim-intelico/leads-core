<?php

declare(strict_types=1);

namespace Leads\Core\CommandBus;

interface CommandMiddlewareInterface
{
    public function execute(CommandInterface $command, callable $next): void;
}
