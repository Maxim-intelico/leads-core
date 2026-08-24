<?php

declare(strict_types=1);

namespace Leads\Core\DependencyInjection;

use Leads\Core\Payload\Cast\ValueCaster;
use Leads\Core\Payload\Source\ValueSource;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class LeadsCoreExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(ValueSource::class)
            ->addTag('leads_core.payload.source');
        $container->registerForAutoconfiguration(ValueCaster::class)
            ->addTag('leads_core.payload.caster');

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config'),
        );

        $loader->load('services.yaml');
    }
}
