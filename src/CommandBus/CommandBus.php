<?php

declare(strict_types=1);

namespace Leads\Core\CommandBus;

final class CommandBus
{
    public function __construct(
        /** @var array<class-string<CommandInterface>, HandlerInterface> */
        private array $commandsMap,
        private CommandValidator $commandValidator,
    ) {
    }

    /**
     * @psalm-suppress MissingReturnType
     */
    public function handle(CommandInterface $command)
    {
        $handler = $this->commandsMap[$command::class] ?? throw new \LogicException('Handler not found.');

        $this->commandValidator->validate($command);

        return $handler->handle($command);
    }
}
