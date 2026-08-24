<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Cast;

use Leads\Core\Payload\Cast\ScalarCaster;
use Leads\Core\Payload\RawValue;
use Leads\Core\Tests\Builder\PropertyPlanBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ScalarCasterTest extends TestCase
{
    #[DataProvider('stringCases')]
    public function testCastString(mixed $input, bool $ok, mixed $expected): void
    {
        $plan = new PropertyPlanBuilder(type: 'string')->build();

        $result = new ScalarCaster()->cast(RawValue::of($input), $plan);

        $this->assertSame($ok, $result->isOk());
        if ($ok) {
            $this->assertSame($expected, $result->value());
        } else {
            $this->assertSame($expected, $result->reasons()[0]->code);
        }
    }

    public static function stringCases(): iterable
    {
        yield 'plain string' => ['john', true, 'john'];
        yield 'int to string' => [5, true, '5'];
        yield 'float to string' => [5.5, true, '5.5'];
        yield 'empty string kept' => ['', true, ''];
        yield 'array rejected' => [['a'], false, 'type.string'];
        yield 'bool rejected' => [true, false, 'type.string'];
        yield 'null rejected' => [null, false, 'type.string'];
    }

    #[DataProvider('floatCases')]
    public function testCastFloat(mixed $input, bool $ok, mixed $expected): void
    {
        $plan = new PropertyPlanBuilder(type: 'float')->build();

        $result = new ScalarCaster()->cast(RawValue::of($input), $plan);

        $this->assertSame($ok, $result->isOk());
        if ($ok) {
            $this->assertSame($expected, $result->value());
        } else {
            $this->assertSame($expected, $result->reasons()[0]->code);
        }
    }

    public static function floatCases(): iterable
    {
        yield 'float' => [5.5, true, 5.5];
        yield 'int to float' => [5, true, 5.0];
        yield 'numeric string' => ['5.5', true, 5.5];
        yield 'empty string rejected' => ['', false, 'type.float'];
        yield 'letters rejected' => ['abc', false, 'type.float'];
        yield 'array rejected' => [[1.0], false, 'type.float'];
    }

    public function testSupportsStringAndFloatOnly(): void
    {
        $caster = new ScalarCaster();

        $this->assertTrue($caster->supports(new PropertyPlanBuilder(type: 'string')->build()));
        $this->assertTrue($caster->supports(new PropertyPlanBuilder(type: 'float')->build()));
        $this->assertFalse($caster->supports(new PropertyPlanBuilder(type: 'int')->build()));
        $this->assertFalse($caster->supports(new PropertyPlanBuilder(type: 'bool')->build()));
    }
}
