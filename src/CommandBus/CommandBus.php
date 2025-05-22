<?php

declare(strict_types=1);

namespace Leads\Core\CommandBus;

final class CommandBus
{
    public function __construct(
        private CommandBusLocator $commandBusLocator,
    ) {
    }

    /**
     * @psalm-suppress MissingReturnType
     */
    public function handle(CommandInterface $command)
    {
        $handler = $this->commandBusLocator->getHandler($command);

        return $handler->handle($command);
    }
}
