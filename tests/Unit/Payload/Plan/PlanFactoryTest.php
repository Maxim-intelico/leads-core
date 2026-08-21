<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Plan;

use Leads\Core\Payload\Cast\BoolCaster;
use Leads\Core\Payload\Cast\CasterRegistry;
use Leads\Core\Payload\Cast\CollectionCaster;
use Leads\Core\Payload\Cast\DateCaster;
use Leads\Core\Payload\Cast\Decorator\ClampCaster;
use Leads\Core\Payload\Cast\Decorator\FallbackCaster;
use Leads\Core\Payload\Cast\Decorator\NullableCaster;
use Leads\Core\Payload\Cast\Decorator\SplitCaster;
use Leads\Core\Payload\Cast\Decorator\TrimCaster;
use Leads\Core\Payload\Cast\EnumCaster;
use Leads\Core\Payload\Cast\FreeFormCaster;
use Leads\Core\Payload\Cast\IntCaster;
use Leads\Core\Payload\Cast\NestedObjectCaster;
use Leads\Core\Payload\Cast\ScalarCaster;
use Leads\Core\Payload\Cast\ScalarCollectionCaster;
use Leads\Core\Payload\Cast\ValueCaster;
use Leads\Core\Payload\ObjectAssembler;
use Leads\Core\Payload\Plan\PlanFactory;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Tests\Fixture\Payload\Invalid\AddressWithSourceAttribute;
use Leads\Core\Tests\Fixture\Payload\Invalid\FreeFormOnNonArrayRequest;
use Leads\Core\Tests\Fixture\Payload\Invalid\FreeFormWithItemTypeRequest;
use Leads\Core\Tests\Fixture\Payload\Invalid\MaxItemsBelowOneRequest;
use Leads\Core\Tests\Fixture\Payload\Invalid\MaxItemsOnNonCollectionRequest;
use Leads\Core\Tests\Fixture\Payload\Invalid\MissingSourceRequest;
use Leads\Core\Tests\Fixture\Payload\Invalid\MultipleSourcesRequest;
use Leads\Core\Tests\Fixture\Payload\Invalid\NestedWithoutValidRequest;
use Leads\Core\Tests\Fixture\Payload\Invalid\NullableItemCollectionRequest;
use Leads\Core\Tests\Fixture\Payload\Invalid\PlainArrayRequest;
use Leads\Core\Tests\Fixture\Payload\Invalid\SplitEmptySeparatorRequest;
use Leads\Core\Tests\Fixture\Payload\Invalid\SplitOnNonCollectionRequest;
use Leads\Core\Tests\Fixture\Payload\Invalid\UnknownTypeRequest;
use Leads\Core\Tests\Fixture\Payload\Invalid\UnsupportedItemCollectionRequest;
use Leads\Core\Tests\Fixture\Payload\SampleFreeFormRequest;
use Leads\Core\Tests\Fixture\Payload\SampleMaxItemsRequest;
use Leads\Core\Tests\Fixture\Payload\SampleRequest;
use Leads\Core\Tests\Fixture\Payload\SampleScalarCollectionRequest;
use Leads\Core\Tests\Fixture\Payload\SampleSortField;
use Leads\Core\Tests\Fixture\Payload\SampleSplitRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PlanFactoryTest extends TestCase
{
    public function testSampleRequestPlanIsBuilt(): void
    {
        $plan = $this->factory()->build(SampleRequest::class);

        $this->assertSame(SampleRequest::class, $plan->class);
        $this->assertSame(2048, $plan->maxBodyBytes);
        $this->assertSame([], $plan->bodyKeys);
        $this->assertCount(9, $plan->properties);
    }

    public function testPointerMapUsesSourcePrefixedPointers(): void
    {
        $plan = $this->factory()->build(SampleRequest::class);

        $this->assertSame('query/toDate', $plan->pointerMap['toDate']);
        $this->assertSame('query/limit', $plan->pointerMap['limit']);
        $this->assertSame('path/projectId', $plan->pointerMap['projectId']);
        $this->assertSame('header/X-Request-Source', $plan->pointerMap['requestSource']);
    }

    public function testHeaderPropertyUsesHeaderNameAsKey(): void
    {
        $plan = $this->factory()->build(SampleRequest::class);

        $requestSource = $this->property($plan->properties, 'requestSource');

        $this->assertSame('X-Request-Source', $requestSource->key);
        $this->assertSame('header', $requestSource->sourceKey);
    }

    public function testEnumPropertyChainIsFallbackAroundEnum(): void
    {
        $plan = $this->factory()->build(SampleRequest::class);

        $chain = $this->property($plan->properties, 'sortBy')->casterChain;

        $this->assertInstanceOf(FallbackCaster::class, $chain);
        $this->assertInstanceOf(EnumCaster::class, $this->unwrap($chain));
    }

    public function testClampedIntChainIsClampAroundInt(): void
    {
        $plan = $this->factory()->build(SampleRequest::class);

        $chain = $this->property($plan->properties, 'limit')->casterChain;

        $this->assertInstanceOf(ClampCaster::class, $chain);
        $this->assertInstanceOf(IntCaster::class, $this->unwrap($chain));
    }

    public function testNullableStringChainIsTrimAroundNullableAroundScalar(): void
    {
        $plan = $this->factory()->build(SampleRequest::class);

        $chain = $this->property($plan->properties, 'email')->casterChain;

        $this->assertInstanceOf(TrimCaster::class, $chain);
        $nullable = $this->unwrap($chain);
        $this->assertInstanceOf(NullableCaster::class, $nullable);
        $this->assertInstanceOf(ScalarCaster::class, $this->unwrap($nullable));
    }

    public function testDatePropertyResolvesToDateCaster(): void
    {
        $plan = $this->factory()->build(SampleRequest::class);

        $chain = $this->property($plan->properties, 'fromDate')->casterChain;

        $this->assertInstanceOf(NullableCaster::class, $chain);
        $this->assertInstanceOf(DateCaster::class, $this->unwrap($chain));
    }

    public function testNullableBoolResolvesToBoolCaster(): void
    {
        $plan = $this->factory()->build(SampleRequest::class);

        $chain = $this->property($plan->properties, 'archived')->casterChain;

        $this->assertInstanceOf(NullableCaster::class, $chain);
        $this->assertInstanceOf(BoolCaster::class, $this->unwrap($chain));
    }

    public function testFallbackKeepsEnumCase(): void
    {
        $plan = $this->factory()->build(SampleRequest::class);

        $sortBy = $this->property($plan->properties, 'sortBy');

        $this->assertTrue($sortBy->hasFallback);
        $this->assertSame(SampleSortField::CreatedAt, $sortBy->fallback);
        $this->assertTrue($sortBy->normalized);
    }

    public function testStringCollectionCompilesItemPlan(): void
    {
        $plan = $this->factory()->build(SampleScalarCollectionRequest::class);

        $sourcesIds = $this->property($plan->properties, 'sourcesIds');

        $this->assertNull($sourcesIds->itemClass);
        $this->assertNotNull($sourcesIds->itemPlan);
        $this->assertInstanceOf(ScalarCollectionCaster::class, $sourcesIds->casterChain);
        $itemChain = $sourcesIds->itemPlan->casterChain;
        $this->assertInstanceOf(TrimCaster::class, $itemChain);
        $this->assertInstanceOf(ScalarCaster::class, $this->unwrap($itemChain));
    }

    public function testIntCollectionItemResolvesToIntCaster(): void
    {
        $plan = $this->factory()->build(SampleScalarCollectionRequest::class);

        $counts = $this->property($plan->properties, 'counts');

        $this->assertInstanceOf(IntCaster::class, $counts->itemPlan?->casterChain);
    }

    public function testEnumCollectionItemGoesToScalarPathWithNormalized(): void
    {
        $plan = $this->factory()->build(SampleScalarCollectionRequest::class);

        $sorts = $this->property($plan->properties, 'sorts');

        $this->assertNull($sorts->itemClass);
        $this->assertSame(SampleSortField::class, $sorts->itemPlan?->enumClass);
        $this->assertTrue($sorts->itemPlan->normalized);
        $this->assertInstanceOf(EnumCaster::class, $sorts->itemPlan->casterChain);
    }

    public function testDateCollectionItemResolvesToDateCaster(): void
    {
        $plan = $this->factory()->build(SampleScalarCollectionRequest::class);

        $dates = $this->property($plan->properties, 'dates');

        $this->assertInstanceOf(DateCaster::class, $dates->itemPlan?->casterChain);
    }

    public function testNullableScalarCollectionWrapsChainInNullable(): void
    {
        $plan = $this->factory()->build(SampleScalarCollectionRequest::class);

        $chain = $this->property($plan->properties, 'tags')->casterChain;

        $this->assertInstanceOf(NullableCaster::class, $chain);
        $this->assertInstanceOf(ScalarCollectionCaster::class, $this->unwrap($chain));
    }

    public function testSplitCollectionChainIsSplitAroundScalarCollection(): void
    {
        $plan = $this->factory()->build(SampleSplitRequest::class);

        $ids = $this->property($plan->properties, 'ids');

        $this->assertNotNull($ids->split);
        $this->assertInstanceOf(SplitCaster::class, $ids->casterChain);
        $this->assertInstanceOf(ScalarCollectionCaster::class, $this->unwrap($ids->casterChain));
    }

    public function testNullableSplitCollectionWrapsInNullable(): void
    {
        $plan = $this->factory()->build(SampleSplitRequest::class);

        $chain = $this->property($plan->properties, 'tags')->casterChain;

        $this->assertInstanceOf(NullableCaster::class, $chain);
        $this->assertInstanceOf(SplitCaster::class, $this->unwrap($chain));
    }

    public function testFreeFormPlanResolvesToFreeFormCaster(): void
    {
        $plan = $this->factory()->build(SampleFreeFormRequest::class);

        $options = $this->property($plan->properties, 'options');

        $this->assertTrue($options->freeForm);
        $this->assertNull($options->itemClass);
        $this->assertNull($options->itemPlan);
        $this->assertInstanceOf(FreeFormCaster::class, $options->casterChain);
    }

    public function testNullableFreeFormWrapsInNullable(): void
    {
        $plan = $this->factory()->build(SampleFreeFormRequest::class);

        $chain = $this->property($plan->properties, 'meta')->casterChain;

        $this->assertInstanceOf(NullableCaster::class, $chain);
        $this->assertInstanceOf(FreeFormCaster::class, $this->unwrap($chain));
    }

    public function testMaxItemsOverridesCollectionLimit(): void
    {
        $plan = $this->factory()->build(SampleMaxItemsRequest::class);

        $this->assertSame(5, $this->property($plan->properties, 'ids')->maxItems);
        $this->assertSame(PropertyPlan::DEFAULT_MAX_ITEMS, $this->property($plan->properties, 'tags')->maxItems);
    }

    #[DataProvider('invalidFixtures')]
    public function testConfigurationErrorsFailAtPlanBuild(string $class, bool $nested, string $messagePart): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage($messagePart);

        $this->factory()->build($class, $nested);
    }

    public static function invalidFixtures(): iterable
    {
        yield 'no source attribute' => [MissingSourceRequest::class, false, 'has no source attribute'];
        yield 'unknown type' => [UnknownTypeRequest::class, false, 'No caster supports type'];
        yield 'nested without Assert\Valid' => [NestedWithoutValidRequest::class, false, 'requires #[Assert\Valid]'];
        yield 'source attribute on nested property' => [AddressWithSourceAttribute::class, true, 'meaningless'];
        yield 'more than one source' => [MultipleSourcesRequest::class, false, 'more than one source'];
        yield 'plain array without item type' => [PlainArrayRequest::class, false, 'plain array'];
        yield 'nullable collection item' => [NullableItemCollectionRequest::class, false, 'Nullable collection items'];
        yield 'unsupported collection item type' => [
            UnsupportedItemCollectionRequest::class,
            false,
            'Unsupported collection item type',
        ];
        yield 'split on non-collection' => [SplitOnNonCollectionRequest::class, false, 'requires a scalar collection'];
        yield 'max items on non-collection' => [
            MaxItemsOnNonCollectionRequest::class,
            false,
            'requires a collection property',
        ];
        yield 'max items below one' => [MaxItemsBelowOneRequest::class, false, 'positive item limit'];
        yield 'split with empty separator' => [SplitEmptySeparatorRequest::class, false, 'empty separator'];
        yield 'freeform on non-array' => [FreeFormOnNonArrayRequest::class, false, 'requires an array-typed property'];
        yield 'freeform with item type' => [FreeFormWithItemTypeRequest::class, false, 'combines #[FreeForm]'];
    }

    private function factory(): PlanFactory
    {
        // Ленивое замыкание реестра — как tagged_iterator в контейнере:
        // NestedObjectCaster зависит от фабрики планов, которая зависит от реестра.
        $casters = new \ArrayObject();
        $registry = new CasterRegistry($casters);
        $factory = new PlanFactory($registry);
        $assembler = new ObjectAssembler();

        $casters->append(new ScalarCaster());
        $casters->append(new IntCaster());
        $casters->append(new BoolCaster());
        $casters->append(new EnumCaster());
        $casters->append(new DateCaster());
        $casters->append(new NestedObjectCaster($factory, $assembler));
        $casters->append(new CollectionCaster($factory, $assembler));
        $casters->append(new ScalarCollectionCaster());
        $casters->append(new FreeFormCaster());

        return $factory;
    }

    /**
     * @param list<PropertyPlan> $properties
     */
    private function property(array $properties, string $name): PropertyPlan
    {
        foreach ($properties as $property) {
            if ($property->name === $name) {
                return $property;
            }
        }

        throw new \LogicException(sprintf('Property %s not found in plan.', $name));
    }

    private function unwrap(ValueCaster $decorator): ValueCaster
    {
        $property = new \ReflectionProperty($decorator, 'inner');

        /** @var ValueCaster */
        return $property->getValue($decorator);
    }
}
