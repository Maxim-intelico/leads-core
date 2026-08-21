<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Cast\Decorator;

use Leads\Core\Payload\Cast\Decorator\TrimCaster;
use Leads\Core\Payload\Cast\ScalarCaster;
use Leads\Core\Payload\RawValue;
use Leads\Core\Tests\Builder\PropertyPlanBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TrimCasterTest extends TestCase
{
    #[DataProvider('trimCases')]
    public function testTrimsWhitespaceIncludingUnicode(string $input, string $expected): void
    {
        $plan = new PropertyPlanBuilder(type: 'string')->build();

        $result = new TrimCaster(new ScalarCaster())->cast(RawValue::of($input), $plan);

        $this->assertTrue($result->isOk());
        $this->assertSame($expected, $result->value());
    }

    public static function trimCases(): iterable
    {
        yield 'plain spaces' => ['  john  ', 'john'];
        yield 'tabs and newlines' => ["\t john \n", 'john'];
        yield 'non-breaking space U+00A0' => ["\u{00A0}john\u{00A0}", 'john'];
        yield 'BOM U+FEFF' => ["\u{FEFF}john\u{FEFF}", 'john'];
        yield 'mixed unicode padding' => ["\u{FEFF}\u{00A0} john \u{00A0}", 'john'];
        yield 'inner spaces kept' => ['  john doe  ', 'john doe'];
    }

    public function testInvalidUtf8IsRejectedBeforeTrim(): void
    {
        $plan = new PropertyPlanBuilder(type: 'string')->build();

        $result = new TrimCaster(new ScalarCaster())->cast(RawValue::of("\xC3\x28"), $plan);

        $this->assertFalse($result->isOk());
        $this->assertSame('type.encoding', $result->reasons()[0]->code);
    }

    public function testNonStringValueIsDelegatedUntouched(): void
    {
        $plan = new PropertyPlanBuilder(type: 'string')->build();

        $result = new TrimCaster(new ScalarCaster())->cast(RawValue::of(5), $plan);

        $this->assertTrue($result->isOk());
        $this->assertSame('5', $result->value());
    }
}
