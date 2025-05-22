<?php

declare(strict_types=1);

namespace Leads\Core\CommandBus;

/**
 * @template TCommand of CommandInterface
 */
interface HandlerInterface
{
    /**
     * @param CommandInterface $command
     *
     * @psalm-suppress MissingReturnType
     */
    public function handle(CommandInterface $command);
}
