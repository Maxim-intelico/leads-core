<?php

declare(strict_types=1);

namespace Leads\Core;

use Leads\Core\DependencyInjection\LeadsCoreExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Kernel\AbstractBundle;

final class LeadsCoreBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new LeadsCoreExtension();
    }
}
