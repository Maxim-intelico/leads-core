<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Cast\Decorator;

use Leads\Core\Payload\Cast\Decorator\ClampCaster;
use Leads\Core\Payload\Cast\IntCaster;
use Leads\Core\Payload\RawValue;
use Leads\Core\Tests\Builder\PropertyPlanBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClampCasterTest extends TestCase
{
    #[DataProvider('cases')]
    public function testClampsIntoRange(mixed $input, int $expected): void
    {
        $plan = new PropertyPlanBuilder(type: 'int')->build();

        $result = new ClampCaster(new IntCaster(), 1, 100)->cast(RawValue::of($input), $plan);

        $this->assertTrue($result->isOk());
        $this->assertSame($expected, $result->value());
    }

    public static function cases(): iterable
    {
        yield 'above max' => ['500', 100];
        yield 'below min' => ['0', 1];
        yield 'negative' => [-5, 1];
        yield 'inside range' => [50, 50];
        yield 'at min' => [1, 1];
        yield 'at max' => [100, 100];
    }

    public function testInnerFailureIsPassedThrough(): void
    {
        $plan = new PropertyPlanBuilder(type: 'int')->build();

        $result = new ClampCaster(new IntCaster(), 1, 100)->cast(RawValue::of('abc'), $plan);

        $this->assertFalse($result->isOk());
        $this->assertSame('type.int', $result->reasons()[0]->code);
    }
}
