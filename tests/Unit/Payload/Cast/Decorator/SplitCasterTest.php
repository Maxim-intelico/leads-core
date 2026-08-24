<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Cast\Decorator;

use Leads\Core\Payload\Cast\Decorator\SplitCaster;
use Leads\Core\Payload\Cast\Decorator\TrimCaster;
use Leads\Core\Payload\Cast\ScalarCaster;
use Leads\Core\Payload\Cast\ScalarCollectionCaster;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Tests\Builder\PropertyPlanBuilder;
use PHPUnit\Framework\TestCase;

final class SplitCasterTest extends TestCase
{
    public function testStringIsSplitIntoList(): void
    {
        $result = $this->caster()->cast(RawValue::of('a,b,c'), $this->plan());

        $this->assertTrue($result->isOk());
        $this->assertSame(['a', 'b', 'c'], $result->value());
    }

    public function testItemsAreTrimmedByItemChain(): void
    {
        $result = $this->caster()->cast(RawValue::of('a, b , c'), $this->plan());

        $this->assertTrue($result->isOk());
        $this->assertSame(['a', 'b', 'c'], $result->value());
    }

    public function testEmptyStringIsEmptyList(): void
    {
        $result = $this->caster()->cast(RawValue::of(''), $this->plan());

        $this->assertTrue($result->isOk());
        $this->assertSame([], $result->value());
    }

    public function testRealListPassesThroughUnchanged(): void
    {
        $result = $this->caster()->cast(RawValue::of(['a', 'b']), $this->plan());

        $this->assertTrue($result->isOk());
        $this->assertSame(['a', 'b'], $result->value());
    }

    public function testNonStringNonArrayFailsInInnerCaster(): void
    {
        $result = $this->caster()->cast(RawValue::of(5), $this->plan());

        $this->assertFalse($result->isOk());
        $this->assertSame('type.collection', $result->reasons()[0]->code);
    }

    public function testCustomSeparator(): void
    {
        $result = $this->caster(separator: '|')->cast(RawValue::of('a|b'), $this->plan());

        $this->assertTrue($result->isOk());
        $this->assertSame(['a', 'b'], $result->value());
    }

    private function caster(string $separator = ','): SplitCaster
    {
        return new SplitCaster(new ScalarCollectionCaster(), $separator);
    }

    private function plan(): PropertyPlan
    {
        $itemPlan = new PropertyPlanBuilder(
            name: 'ids[]',
            key: '',
            sourceKey: '',
            pointer: '',
            isStringLike: true,
        )->build();

        return new PropertyPlanBuilder(
            name: 'ids',
            key: 'ids',
            sourceKey: 'query',
            pointer: 'query/ids',
            type: 'array',
            itemPlan: $itemPlan->withCasterChain(new TrimCaster(new ScalarCaster())),
        )->build();
    }
}
