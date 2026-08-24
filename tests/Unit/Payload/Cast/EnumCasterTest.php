<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Cast;

use Leads\Core\Payload\Cast\EnumCaster;
use Leads\Core\Payload\RawValue;
use Leads\Core\Tests\Builder\PropertyPlanBuilder;
use Leads\Core\Tests\Fixture\Payload\SampleSortField;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EnumCasterTest extends TestCase
{
    public function testExactValueMatches(): void
    {
        $result = new EnumCaster()->cast(RawValue::of('lastName'), $this->plan());

        $this->assertTrue($result->isOk());
        $this->assertSame(SampleSortField::LastName, $result->value());
    }

    public function testUnknownValueIsRecoverableError(): void
    {
        $result = new EnumCaster()->cast(RawValue::of('password'), $this->plan());

        $this->assertFalse($result->isOk());
        $this->assertSame('enum.unknown', $result->reasons()[0]->code);
    }

    #[DataProvider('normalizedCases')]
    public function testNormalizedMatchesCaseInsensitively(string $input, SampleSortField $expected): void
    {
        $result = new EnumCaster()->cast(RawValue::of($input), $this->plan(normalized: true));

        $this->assertTrue($result->isOk());
        $this->assertSame($expected, $result->value());
    }

    public static function normalizedCases(): iterable
    {
        yield 'upper camelCase value' => ['LASTNAME', SampleSortField::LastName];
        yield 'lower camelCase value' => ['lastname', SampleSortField::LastName];
        yield 'exact value' => ['createdAt', SampleSortField::CreatedAt];
        yield 'padded value' => [' id ', SampleSortField::Id];
    }

    public function testNormalizedUnknownValueIsRecoverableError(): void
    {
        $result = new EnumCaster()->cast(RawValue::of('phone'), $this->plan(normalized: true));

        $this->assertFalse($result->isOk());
        $this->assertSame('enum.unknown', $result->reasons()[0]->code);
    }

    public function testArrayIsTypeError(): void
    {
        $result = new EnumCaster()->cast(RawValue::of(['a', 'b']), $this->plan());

        $this->assertFalse($result->isOk());
        $this->assertSame('type.enum', $result->reasons()[0]->code);
    }

    public function testNormalizedArrayIsStillTypeError(): void
    {
        $result = new EnumCaster()->cast(RawValue::of(['a', 'b']), $this->plan(normalized: true));

        $this->assertFalse($result->isOk());
        $this->assertSame('type.enum', $result->reasons()[0]->code);
    }

    private function plan(bool $normalized = false): \Leads\Core\Payload\Plan\PropertyPlan
    {
        return new PropertyPlanBuilder(
            type: SampleSortField::class,
            enumClass: SampleSortField::class,
            normalized: $normalized,
        )->build();
    }
}
