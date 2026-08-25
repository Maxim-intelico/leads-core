<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Cast;

use Leads\Core\Payload\Cast\DateCaster;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Tests\Builder\PropertyPlanBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateCasterTest extends TestCase
{
    public function testDateIsParsedWithZeroedTime(): void
    {
        $result = new DateCaster()->cast(RawValue::of('2026-08-07'), $this->plan());

        $this->assertTrue($result->isOk());
        $value = $result->value();
        $this->assertInstanceOf(\DateTimeImmutable::class, $value);
        $this->assertSame('2026-08-07 00:00:00', $value->format('Y-m-d H:i:s'));
    }

    #[DataProvider('dateTimeCases')]
    public function testDateTimeFormatsAreParsed(string $input, string $expected): void
    {
        $result = new DateCaster()->cast(RawValue::of($input), $this->plan());

        $this->assertTrue($result->isOk());
        $value = $result->value();
        $this->assertInstanceOf(\DateTimeImmutable::class, $value);
        $this->assertSame($expected, $value->format('Y-m-d H:i:s'));
    }

    public static function dateTimeCases(): iterable
    {
        yield 'sql datetime' => ['2026-08-07 10:30:00', '2026-08-07 10:30:00'];
        yield 'iso 8601 with offset' => ['2026-08-07T10:30:00+00:00', '2026-08-07 10:30:00'];
        yield 'iso 8601 without offset' => ['2026-08-07T10:30:00', '2026-08-07 10:30:00'];
        yield 'iso 8601 without seconds' => ['2026-08-25T00:00', '2026-08-25 00:00:00'];
        yield 'sql datetime without seconds' => ['2026-08-25 14:30', '2026-08-25 14:30:00'];
    }

    public function testMinutePrecisionDateTimeHasZeroedSecondsAndMicroseconds(): void
    {
        $result = new DateCaster()->cast(RawValue::of('2026-08-25T14:30'), $this->plan());

        $this->assertTrue($result->isOk());
        $value = $result->value();
        $this->assertInstanceOf(\DateTimeImmutable::class, $value);
        $this->assertSame('2026-08-25 14:30:00.000000', $value->format('Y-m-d H:i:s.u'));
    }

    #[DataProvider('invalidCases')]
    public function testInvalidValuesAreRejected(mixed $input): void
    {
        $result = new DateCaster()->cast(RawValue::of($input), $this->plan());

        $this->assertFalse($result->isOk());
        $this->assertSame('type.date', $result->reasons()[0]->code);
    }

    public static function invalidCases(): iterable
    {
        yield 'wrong format' => ['07.08.2026'];
        yield 'impossible date silently rolled over by PHP' => ['2026-02-31'];
        yield 'impossible datetime' => ['2026-02-31 10:00:00'];
        yield 'impossible datetime without seconds' => ['2026-02-31T10:00'];
        yield 'empty string' => [''];
        yield 'null' => [null];
        yield 'array' => [['2026-08-07']];
        yield 'garbage' => ['not-a-date'];
    }

    private function plan(): PropertyPlan
    {
        return new PropertyPlanBuilder(type: \DateTimeImmutable::class)->build();
    }
}
