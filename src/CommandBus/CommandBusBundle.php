<?php

declare(strict_types=1);

namespace Leads\Core\CommandBus;

use Leads\Core\DependencyInjection\Compiler\CommandBusCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class CommandBusBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new CommandBusCompilerPass());
    }
}
