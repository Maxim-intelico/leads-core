<?php

declare(strict_types=1);

namespace Leads\Core\DependencyInjection;

use Leads\Core\CommandBus\CommandValidatorInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Leads\Core\CommandBus\HandlerInterface;

final class LeadsCoreExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yaml');
        $container
            ->registerForAutoconfiguration(HandlerInterface::class)
            ->addTag('leads-core.use-case.handler');
        $container
            ->registerForAutoconfiguration(CommandValidatorInterface::class)
            ->addTag('leads-core.use-case.validator');
    }
}
