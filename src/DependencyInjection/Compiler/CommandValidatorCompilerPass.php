<?php

declare(strict_types=1);

namespace Leads\Core\DependencyInjection\Compiler;

use Leads\Core\CommandBus\AsCommandValidator;
use Leads\Core\CommandBus\CommandValidator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class CommandValidatorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $validatorsMap = [];
        $locatorMap = [];

        foreach ($container->findTaggedServiceIds('leads-core.use-case.validator') as $id => $definition) {
            $definition = $container->getDefinition($id);
            $class = $definition->getClass();
            if ($class === null || !class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            $attributes = $reflection->getAttributes(AsCommandValidator::class);
            if ($attributes === []) {
                continue;
            }

            /** @var AsCommandValidator $attr */
            $attr = $attributes[0]->newInstance();
            $validatorsMap[$attr->commandClass][] = $id;
            $locatorMap[$id] = new Reference($id);
        }

        $validators = ServiceLocatorTagPass::register($container, $locatorMap, CommandValidator::class);

        $container
            ->register(CommandValidator::class, CommandValidator::class)
            ->setArguments([$validators, $validatorsMap])
            ->setPublic(true);
    }
}
