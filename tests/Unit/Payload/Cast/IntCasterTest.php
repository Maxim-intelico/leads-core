<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Cast;

use Leads\Core\Payload\Cast\IntCaster;
use Leads\Core\Payload\RawValue;
use Leads\Core\Tests\Builder\PropertyPlanBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IntCasterTest extends TestCase
{
    #[DataProvider('cases')]
    public function testCast(mixed $input, bool $ok, mixed $expected): void
    {
        $plan = new PropertyPlanBuilder(type: 'int')->build();

        $result = new IntCaster()->cast(RawValue::of($input), $plan);

        $this->assertSame($ok, $result->isOk());
        if ($ok) {
            $this->assertSame($expected, $result->value());
        } else {
            $this->assertSame($expected, $result->reasons()[0]->code);
        }
    }

    public static function cases(): iterable
    {
        yield 'int' => [42, true, 42];
        yield 'numeric string' => ['42', true, 42];
        yield 'negative string' => ['-7', true, -7];
        yield 'empty string rejected' => ['', false, 'type.int'];
        yield 'null rejected' => [null, false, 'type.int'];
        yield 'float string rejected' => ['5.5', false, 'type.int'];
        yield 'letters rejected' => ['abc', false, 'type.int'];
        yield 'bool rejected' => [true, false, 'type.int'];
        yield 'float rejected' => [5.5, false, 'type.int'];
        yield 'array rejected' => [[1], false, 'type.int'];
    }
}
