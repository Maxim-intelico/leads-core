<?php

declare(strict_types=1);

namespace Leads\Core\CommandBus;

use Psr\Container\ContainerInterface;

final readonly class CommandBus
{
    /**
     * @param ContainerInterface $handlers service locator keyed by command class-string
     */
    public function __construct(
        private ContainerInterface $handlers,
        private CommandValidator $commandValidator,
    ) {
    }

    public function handle(CommandInterface $command): mixed
    {
        $this->commandValidator->validate($command);

        if (!$this->handlers->has($command::class)) {
            throw new HandlerNotFoundException($command::class);
        }

        /** @var HandlerInterface $handler */
        $handler = $this->handlers->get($command::class);

        return $handler->handle($command);
    }
}
