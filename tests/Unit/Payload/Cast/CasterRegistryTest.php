<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Cast;

use Leads\Core\Payload\Cast\BoolCaster;
use Leads\Core\Payload\Cast\CasterRegistry;
use Leads\Core\Payload\Cast\IntCaster;
use Leads\Core\Payload\Cast\ScalarCaster;
use Leads\Core\Tests\Builder\PropertyPlanBuilder;
use PHPUnit\Framework\TestCase;

final class CasterRegistryTest extends TestCase
{
    public function testResolvesFirstSupportingCaster(): void
    {
        $registry = new CasterRegistry([new ScalarCaster(), new IntCaster(), new BoolCaster()]);

        $resolved = $registry->resolve(new PropertyPlanBuilder(type: 'int')->build());

        $this->assertInstanceOf(IntCaster::class, $resolved);
    }

    public function testReturnsNullWhenNoCasterSupportsType(): void
    {
        $registry = new CasterRegistry([new ScalarCaster(), new IntCaster()]);

        $resolved = $registry->resolve(new PropertyPlanBuilder(type: \SplStack::class)->build());

        $this->assertNull($resolved);
    }
}
