<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Integration;

use Leads\Core\LeadsCoreBundle;
use Leads\Core\Payload\PayloadMapper;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * Минимальное ядро для интеграционных тестов бандла: FrameworkBundle + LeadsCoreBundle,
 * без роутов и собственной конфигурации — проверяется ровно та проводка,
 * которую получит потребитель библиотеки.
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new LeadsCoreBundle()];
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/leads-core-test-kernel/cache/' . $this->environment;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/leads-core-test-kernel/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'test',
            'test' => true,
            'http_method_override' => false,
            'php_errors' => ['log' => true],
            'validation' => ['email_validation_mode' => 'html5'],
        ]);

        // Сервисы бандла приватные — для теста проводки нужен публичный алиас.
        $container->services()
            ->alias('leads_core.test.payload_mapper', PayloadMapper::class)
            ->public();
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
    }
}
