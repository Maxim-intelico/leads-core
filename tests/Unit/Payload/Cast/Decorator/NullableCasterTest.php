<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Cast\Decorator;

use Leads\Core\Payload\Cast\Decorator\NullableCaster;
use Leads\Core\Payload\Cast\IntCaster;
use Leads\Core\Payload\Cast\ScalarCaster;
use Leads\Core\Payload\RawValue;
use Leads\Core\Tests\Builder\PropertyPlanBuilder;
use PHPUnit\Framework\TestCase;

final class NullableCasterTest extends TestCase
{
    public function testNullBecomesNull(): void
    {
        $plan = new PropertyPlanBuilder(type: 'string')->build();

        $result = new NullableCaster(new ScalarCaster())->cast(RawValue::of(null), $plan);

        $this->assertTrue($result->isOk());
        $this->assertNull($result->value());
    }

    public function testBlankStringForStringTypeBecomesNull(): void
    {
        $plan = new PropertyPlanBuilder(type: 'string')->build();

        $result = new NullableCaster(new ScalarCaster())->cast(RawValue::of(''), $plan);

        $this->assertTrue($result->isOk());
        $this->assertNull($result->value());
    }

    public function testSpacesOnlyStringForStringTypeBecomesNull(): void
    {
        $plan = new PropertyPlanBuilder(type: 'string')->build();

        $result = new NullableCaster(new ScalarCaster())->cast(RawValue::of('   '), $plan);

        $this->assertTrue($result->isOk());
        $this->assertNull($result->value());
    }

    public function testBlankStringForIntTypeIsNotSwallowed(): void
    {
        $plan = new PropertyPlanBuilder(type: 'int')->build();

        $result = new NullableCaster(new IntCaster())->cast(RawValue::of(''), $plan);

        $this->assertFalse($result->isOk());
        $this->assertSame('type.int', $result->reasons()[0]->code);
    }

    public function testNonBlankValueIsDelegated(): void
    {
        $plan = new PropertyPlanBuilder(type: 'string')->build();

        $result = new NullableCaster(new ScalarCaster())->cast(RawValue::of('john'), $plan);

        $this->assertTrue($result->isOk());
        $this->assertSame('john', $result->value());
    }
}
