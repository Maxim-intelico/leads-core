<?php

declare(strict_types=1);

namespace Leads\Core\DependencyInjection\Compiler;

use Leads\Core\CommandBus\AsCommandHandler;
use Leads\Core\CommandBus\CommandBus;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class CommandBusCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $commandsMap = [];

        foreach ($container->findTaggedServiceIds('leads-core.use-case.handler') as $id => $definition) {
            $definition = $container->getDefinition($id);
            $class = $definition->getClass();
            if ($class === null || !class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            $attributes = $reflection->getAttributes(AsCommandHandler::class);
            if ($attributes === []) {
                continue;
            }

            /** @var AsCommandHandler $attr */
            $attr = $attributes[0]->newInstance();
            $commandsMap[$attr->commandClass] = new Reference($id);
        }

        $container
            ->register(CommandBus::class, CommandBus::class)
            ->setArguments([$commandsMap])
            ->setPublic(true);
    }
}
