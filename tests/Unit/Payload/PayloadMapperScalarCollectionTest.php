<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload;

use Leads\Core\Payload\Cast\BoolCaster;
use Leads\Core\Payload\Cast\CasterRegistry;
use Leads\Core\Payload\Cast\CollectionCaster;
use Leads\Core\Payload\Cast\DateCaster;
use Leads\Core\Payload\Cast\EnumCaster;
use Leads\Core\Payload\Cast\IntCaster;
use Leads\Core\Payload\Cast\NestedObjectCaster;
use Leads\Core\Payload\Cast\ScalarCaster;
use Leads\Core\Payload\Cast\ScalarCollectionCaster;
use Leads\Core\Payload\Error\PayloadViolationException;
use Leads\Core\Payload\ObjectAssembler;
use Leads\Core\Payload\PayloadMapper;
use Leads\Core\Payload\Plan\PlanFactory;
use Leads\Core\Payload\Source\BodySource;
use Leads\Core\Payload\Source\HeaderSource;
use Leads\Core\Payload\Source\PathSource;
use Leads\Core\Payload\Source\QuerySource;
use Leads\Core\Tests\Fixture\Payload\SampleScalarCollectionRequest;
use Leads\Core\Tests\Fixture\Payload\SampleSortField;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validation;

final class PayloadMapperScalarCollectionTest extends TestCase
{
    public function testScalarCollectionsAreCastPerItem(): void
    {
        $dto = $this->map([
            'sourcesIds' => [' abc ', 'def', 5],
            'counts' => ['5', 7],
            'sorts' => ['CREATEDAT', 'lastName'],
            'dates' => ['2026-08-07'],
            'tags' => ['x'],
        ]);

        $this->assertSame(['abc', 'def', '5'], $dto->sourcesIds);
        $this->assertSame([5, 7], $dto->counts);
        $this->assertSame([SampleSortField::CreatedAt, SampleSortField::LastName], $dto->sorts);
        $this->assertCount(1, $dto->dates);
        $this->assertSame('2026-08-07', $dto->dates[0]->format('Y-m-d'));
        $this->assertSame(['x'], $dto->tags);
    }

    public function testMissingKeysFallBackToDefaults(): void
    {
        $dto = $this->map([]);

        $this->assertSame([], $dto->sourcesIds);
        $this->assertSame([], $dto->counts);
        $this->assertNull($dto->tags);
    }

    public function testNullForNullableCollectionStaysNull(): void
    {
        $dto = $this->map(['tags' => null]);

        $this->assertNull($dto->tags);
    }

    public function testBrokenItemPointsToElementIndex(): void
    {
        try {
            $this->map(['sourcesIds' => ['a', 'b', ['broken']]]);
        } catch (PayloadViolationException $payloadViolationException) {
            $violation = $payloadViolationException->getViolations()->all()[0];
            $this->assertSame('type.string', $violation->code);
            $this->assertSame('body/sourcesIds/2', $violation->pointer);

            return;
        }

        $this->fail('Expected PayloadViolationException.');
    }

    public function testAssociativeArrayInsteadOfListIsTypeError(): void
    {
        try {
            $this->map(['sourcesIds' => ['a' => 'b']]);
        } catch (PayloadViolationException $payloadViolationException) {
            $violation = $payloadViolationException->getViolations()->all()[0];
            $this->assertSame('type.collection', $violation->code);
            $this->assertSame('body/sourcesIds', $violation->pointer);

            return;
        }

        $this->fail('Expected PayloadViolationException.');
    }

    public function testUnknownEnumItemPointsToElementIndex(): void
    {
        try {
            $this->map(['sorts' => ['createdAt', 'password']]);
        } catch (PayloadViolationException $payloadViolationException) {
            $violation = $payloadViolationException->getViolations()->all()[0];
            $this->assertSame('enum.unknown', $violation->code);
            $this->assertSame('body/sorts/1', $violation->pointer);

            return;
        }

        $this->fail('Expected PayloadViolationException.');
    }

    private function map(array $body): SampleScalarCollectionRequest
    {
        $request = Request::create(
            uri: '/samples',
            method: Request::METHOD_POST,
            content: (string)json_encode($body),
        );

        return $this->mapper()->map(SampleScalarCollectionRequest::class, $request);
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
