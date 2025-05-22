<?php

declare(strict_types=1);

namespace Leads\Core\CommandBus;

/**
 * @template TCommand of CommandInterface
 */
interface CommandValidatorInterface
{
    /**
     * @param CommandInterface $command
     */
    public function validate(CommandInterface $command): void;
}
