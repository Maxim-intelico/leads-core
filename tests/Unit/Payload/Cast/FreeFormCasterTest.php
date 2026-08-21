<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Cast;

use Leads\Core\Payload\Cast\Decorator\TrimCaster;
use Leads\Core\Payload\Cast\FreeFormCaster;
use Leads\Core\Payload\Cast\ScalarCaster;
use Leads\Core\Payload\RawValue;
use Leads\Core\Tests\Builder\PropertyPlanBuilder;
use PHPUnit\Framework\TestCase;

final class FreeFormCasterTest extends TestCase
{
    public function testSupportsOnlyFreeFormPlans(): void
    {
        $caster = new FreeFormCaster();

        $this->assertTrue($caster->supports(new PropertyPlanBuilder(type: 'array', freeForm: true)->build()));
        $this->assertFalse($caster->supports(new PropertyPlanBuilder(type: 'array')->build()));

        $itemPlan = new PropertyPlanBuilder(isStringLike: true)->build()
            ->withCasterChain(new TrimCaster(new ScalarCaster()));
        $this->assertFalse($caster->supports(new PropertyPlanBuilder(type: 'array', itemPlan: $itemPlan)->build()));
    }

    public function testAssociativeArrayPassesAsIs(): void
    {
        $value = ['a' => ['b' => 1], 'c' => [1, 2, 3]];

        $result = new FreeFormCaster()->cast(RawValue::of($value), $this->plan());

        $this->assertTrue($result->isOk());
        $this->assertSame($value, $result->value());
    }

    public function testListPassesAsIs(): void
    {
        $result = new FreeFormCaster()->cast(RawValue::of([1, 'two', null]), $this->plan());

        $this->assertTrue($result->isOk());
        $this->assertSame([1, 'two', null], $result->value());
    }

    public function testStringIsTypeError(): void
    {
        $result = new FreeFormCaster()->cast(RawValue::of('x'), $this->plan());

        $this->assertFalse($result->isOk());
        $this->assertSame('type.array', $result->reasons()[0]->code);
    }

    public function testIntIsTypeError(): void
    {
        $result = new FreeFormCaster()->cast(RawValue::of(5), $this->plan());

        $this->assertFalse($result->isOk());
        $this->assertSame('type.array', $result->reasons()[0]->code);
    }

    private function plan(): \Leads\Core\Payload\Plan\PropertyPlan
    {
        return new PropertyPlanBuilder(
            name: 'options',
            key: 'options',
            sourceKey: 'body',
            pointer: 'body/options',
            type: 'array',
            freeForm: true,
        )->build();
    }
}
