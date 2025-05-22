<?php

declare(strict_types=1);

namespace Leads\Core\CommandBus;

final readonly class CommandBusLocator
{
    public function __construct(
        /** @var array<class-string<CommandInterface>, HandlerInterface> */
        private array $commandsMap,
    ) {
    }

    public function getHandler(CommandInterface $command): HandlerInterface
    {
        return $this->commandsMap[$command::class] ?? throw new \LogicException('Handler not found');
    }
}
