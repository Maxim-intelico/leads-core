<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Cast;

use Leads\Core\Payload\Cast\Decorator\TrimCaster;
use Leads\Core\Payload\Cast\ScalarCaster;
use Leads\Core\Payload\Cast\ScalarCollectionCaster;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Tests\Builder\PropertyPlanBuilder;
use PHPUnit\Framework\TestCase;

final class ScalarCollectionCasterTest extends TestCase
{
    public function testSupportsOnlyPlansWithItemPlan(): void
    {
        $caster = new ScalarCollectionCaster();

        $this->assertTrue($caster->supports($this->plan()));
        $this->assertFalse($caster->supports(new PropertyPlanBuilder(type: 'array')->build()));
    }

    public function testItemsAreCastAndTrimmedIndividually(): void
    {
        $result = new ScalarCollectionCaster()->cast(RawValue::of([' abc ', 'def', 5]), $this->plan());

        $this->assertTrue($result->isOk());
        $this->assertSame(['abc', 'def', '5'], $result->value());
    }

    public function testEmptyListIsOk(): void
    {
        $result = new ScalarCollectionCaster()->cast(RawValue::of([]), $this->plan());

        $this->assertTrue($result->isOk());
        $this->assertSame([], $result->value());
    }

    public function testAssociativeArrayIsCollectionTypeError(): void
    {
        $result = new ScalarCollectionCaster()->cast(RawValue::of(['a' => 'b']), $this->plan());

        $this->assertFalse($result->isOk());
        $this->assertSame('type.collection', $result->reasons()[0]->code);
    }

    public function testScalarInsteadOfListIsCollectionTypeError(): void
    {
        $result = new ScalarCollectionCaster()->cast(RawValue::of('abc'), $this->plan());

        $this->assertFalse($result->isOk());
        $this->assertSame('type.collection', $result->reasons()[0]->code);
    }

    public function testOversizedListIsRejectedBeforeCastingItems(): void
    {
        $result = new ScalarCollectionCaster()->cast(RawValue::of(['a', 'b', 'c']), $this->plan(maxItems: 2));

        $this->assertFalse($result->isOk());
        $this->assertSame('collection.too_many', $result->reasons()[0]->code);
    }

    public function testBrokenItemIsNestedByIndex(): void
    {
        $result = new ScalarCollectionCaster()->cast(RawValue::of(['ok', ['broken'], 'ok']), $this->plan());

        $this->assertFalse($result->isOk());
        $this->assertSame('type.string', $result->reasons()[0]->code);
        $this->assertSame('1', $result->reasons()[0]->path);
    }

    public function testFailFastOnTwoBrokenItemsReportsSingleReason(): void
    {
        $result = new ScalarCollectionCaster()->cast(RawValue::of([['broken'], ['also-broken']]), $this->plan());

        $this->assertFalse($result->isOk());
        $this->assertCount(1, $result->reasons());
        $this->assertSame('0', $result->reasons()[0]->path);
    }

    public function testNullItemIsTypeError(): void
    {
        $result = new ScalarCollectionCaster()->cast(RawValue::of([null]), $this->plan());

        $this->assertFalse($result->isOk());
        $this->assertSame('type.string', $result->reasons()[0]->code);
        $this->assertSame('0', $result->reasons()[0]->path);
    }

    private function plan(int $maxItems = 1000): PropertyPlan
    {
        $itemPlan = new PropertyPlanBuilder(
            name: 'sourcesIds[]',
            key: '',
            sourceKey: '',
            pointer: '',
            isStringLike: true,
        )->build();

        return new PropertyPlanBuilder(
            name: 'sourcesIds',
            key: 'sourcesIds',
            sourceKey: 'body',
            pointer: 'body/sourcesIds',
            type: 'array',
            itemPlan: $itemPlan->withCasterChain(new TrimCaster(new ScalarCaster())),
            maxItems: $maxItems,
        )->build();
    }
}
