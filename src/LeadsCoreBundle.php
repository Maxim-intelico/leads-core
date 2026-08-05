<?php

declare(strict_types=1);

namespace Leads\Core;

use Leads\Core\DependencyInjection\LeadsCoreExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class LeadsCoreBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new LeadsCoreExtension();
    }
}
