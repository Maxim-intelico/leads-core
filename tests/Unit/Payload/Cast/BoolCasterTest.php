<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Cast;

use Leads\Core\Payload\Cast\BoolCaster;
use Leads\Core\Payload\RawValue;
use Leads\Core\Tests\Builder\PropertyPlanBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BoolCasterTest extends TestCase
{
    #[DataProvider('cases')]
    public function testCast(mixed $input, bool $ok, mixed $expected): void
    {
        $plan = new PropertyPlanBuilder(type: 'bool')->build();

        $result = new BoolCaster()->cast(RawValue::of($input), $plan);

        $this->assertSame($ok, $result->isOk());
        if ($ok) {
            $this->assertSame($expected, $result->value());
        } else {
            $this->assertSame($expected, $result->reasons()[0]->code);
        }
    }

    public static function cases(): iterable
    {
        yield 'true' => [true, true, true];
        yield 'false' => [false, true, false];
        yield 'string true' => ['true', true, true];
        yield 'string false' => ['false', true, false];
        yield 'string 1' => ['1', true, true];
        yield 'string 0' => ['0', true, false];
        yield 'string on' => ['on', true, true];
        yield 'string off' => ['off', true, false];
        yield 'int 1' => [1, true, true];
        yield 'int 0' => [0, true, false];
        yield 'letters rejected' => ['abc', false, 'type.bool'];
        yield 'null rejected' => [null, false, 'type.bool'];
        yield 'array rejected' => [[true], false, 'type.bool'];
    }
}
