<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Cast\Decorator;

use Leads\Core\Payload\Cast\Decorator\FallbackCaster;
use Leads\Core\Payload\Cast\EnumCaster;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Tests\Builder\PropertyPlanBuilder;
use Leads\Core\Tests\Fixture\Payload\SampleSortField;
use PHPUnit\Framework\TestCase;

final class FallbackCasterTest extends TestCase
{
    public function testSuccessfulCastIsPassedThrough(): void
    {
        $result = $this->caster()->cast(RawValue::of('lastName'), $this->plan());

        $this->assertTrue($result->isOk());
        $this->assertSame(SampleSortField::LastName, $result->value());
    }

    public function testRecoverableErrorIsReplacedWithFallback(): void
    {
        $result = $this->caster()->cast(RawValue::of('password'), $this->plan());

        $this->assertTrue($result->isOk());
        $this->assertSame(SampleSortField::CreatedAt, $result->value());
    }

    public function testTypeErrorIsNotSwallowed(): void
    {
        $result = $this->caster()->cast(RawValue::of(['a', 'b']), $this->plan());

        $this->assertFalse($result->isOk());
        $this->assertSame('type.enum', $result->reasons()[0]->code);
    }

    private function caster(): FallbackCaster
    {
        return new FallbackCaster(new EnumCaster(), SampleSortField::CreatedAt);
    }

    private function plan(): PropertyPlan
    {
        return new PropertyPlanBuilder(
            type: SampleSortField::class,
            enumClass: SampleSortField::class,
            fallback: SampleSortField::CreatedAt,
            hasFallback: true,
        )->build();
    }
}
