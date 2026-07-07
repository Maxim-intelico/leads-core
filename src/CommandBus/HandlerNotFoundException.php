<?php

declare(strict_types=1);

namespace Leads\Core\CommandBus;

final class HandlerNotFoundException extends \LogicException
{
    /**
     * @param class-string<CommandInterface> $commandClass
     */
    public function __construct(string $commandClass)
    {
        parent::__construct(sprintf('No handler registered for command "%s".', $commandClass));
    }
}
