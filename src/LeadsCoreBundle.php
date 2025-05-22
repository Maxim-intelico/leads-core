<?php

declare(strict_types=1);

namespace Leads\Core;

use Leads\Core\DependencyInjection\Compiler\CommandBusCompilerPass;
use Leads\Core\DependencyInjection\Compiler\CommandValidatorCompilerPass;
use Leads\Core\DependencyInjection\LeadsCoreExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class LeadsCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CommandBusCompilerPass());
        $container->addCompilerPass(new CommandValidatorCompilerPass());
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new LeadsCoreExtension();
    }
}
