<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload;

use Leads\Core\Payload\Cast\BoolCaster;
use Leads\Core\Payload\Cast\CasterRegistry;
use Leads\Core\Payload\Cast\CollectionCaster;
use Leads\Core\Payload\Cast\DateCaster;
use Leads\Core\Payload\Cast\EnumCaster;
use Leads\Core\Payload\Cast\FreeFormCaster;
use Leads\Core\Payload\Cast\IntCaster;
use Leads\Core\Payload\Cast\NestedObjectCaster;
use Leads\Core\Payload\Cast\ScalarCaster;
use Leads\Core\Payload\Cast\ScalarCollectionCaster;
use Leads\Core\Payload\ObjectAssembler;
use Leads\Core\Payload\PayloadMapper;
use Leads\Core\Payload\Plan\PlanFactory;
use Leads\Core\Payload\Source\BodySource;
use Leads\Core\Payload\Source\HeaderSource;
use Leads\Core\Payload\Source\PathSource;
use Leads\Core\Payload\Source\QuerySource;
use Leads\Core\Tests\Fixture\Payload\SampleSplitRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validation;

final class PayloadMapperSplitTest extends TestCase
{
    public function testCsvStringBecomesList(): void
    {
        $dto = $this->map(['ids' => 'a,b,c']);

        $this->assertSame(['a', 'b', 'c'], $dto->ids);
    }

    public function testItemsAreTrimmed(): void
    {
        $dto = $this->map(['ids' => 'a, b , c']);

        $this->assertSame(['a', 'b', 'c'], $dto->ids);
    }

    public function testEmptyStringIsEmptyList(): void
    {
        $dto = $this->map(['ids' => '']);

        $this->assertSame([], $dto->ids);
    }

    public function testRealListSyntaxStillWorks(): void
    {
        $dto = $this->map(['ids' => ['a', 'b']]);

        $this->assertSame(['a', 'b'], $dto->ids);
    }

    public function testCsvInsideNestedFilterIsSplit(): void
    {
        $dto = $this->map(['filter' => ['ids' => 'a,b,c']]);

        $this->assertNotNull($dto->filter);
        $this->assertSame(['a', 'b', 'c'], $dto->filter->ids);
    }

    public function testCustomSeparatorWithIntItems(): void
    {
        $dto = $this->map(['counts' => '1|2']);

        $this->assertSame([1, 2], $dto->counts);
    }

    public function testMissingKeysFallBackToDefaults(): void
    {
        $dto = $this->map([]);

        $this->assertSame([], $dto->ids);
        $this->assertSame([], $dto->counts);
        $this->assertNull($dto->tags);
        $this->assertNull($dto->filter);
    }

    private function map(array $query): SampleSplitRequest
    {
        $request = Request::create(uri: '/samples', method: Request::METHOD_GET, parameters: $query);

        return $this->mapper()->map(SampleSplitRequest::class, $request);
    }

    private function mapper(): PayloadMapper
    {
        $assembler = new ObjectAssembler();

        // Ленивое замыкание реестра — как tagged_iterator в контейнере: Nested/Collection
        // кастеры зависят от фабрики планов, которая зависит от реестра.
        $casters = new \ArrayObject();
        $registry = new CasterRegistry($casters);
        $factory = new PlanFactory($registry);

        $casters->append(new ScalarCaster());
        $casters->append(new IntCaster());
        $casters->append(new BoolCaster());
        $casters->append(new EnumCaster());
        $casters->append(new DateCaster());
        $casters->append(new NestedObjectCaster($factory, $assembler));
        $casters->append(new CollectionCaster($factory, $assembler));
        $casters->append(new ScalarCollectionCaster());
        $casters->append(new FreeFormCaster());

        return new PayloadMapper(
            plans: $factory,
            assembler: $assembler,
            sources: [
                'body' => new BodySource(),
                'query' => new QuerySource(),
                'path' => new PathSource(),
                'header' => new HeaderSource(),
            ],
            validator: Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
            logger: new NullLogger(),
        );
    }
}
