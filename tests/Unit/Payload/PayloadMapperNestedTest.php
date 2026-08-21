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
use Leads\Core\Tests\Fixture\Payload\SampleAddress;
use Leads\Core\Tests\Fixture\Payload\SampleItem;
use Leads\Core\Tests\Fixture\Payload\SampleNestedRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

final class PayloadMapperNestedTest extends TestCase
{
    public function testNestedObjectAndCollectionAreAssembled(): void
    {
        $dto = $this->map([
            'title' => 'order',
            'address' => ['city' => 'Yerevan', 'zip' => '0010'],
            'items' => [
                ['sku' => 'a-1', 'quantity' => 2],
                ['sku' => 'b-2', 'quantity' => 5],
            ],
            'scheduledAt' => '2026-08-07 10:30:00',
            'totalPrice' => '99.90',
        ]);

        $this->assertSame(99.9, $dto->totalPrice);
        $this->assertSame('order', $dto->title);
        $this->assertInstanceOf(SampleAddress::class, $dto->address);
        $this->assertSame('Yerevan', $dto->address->city);
        $this->assertCount(2, $dto->items);
        $this->assertInstanceOf(SampleItem::class, $dto->items[0]);
        $this->assertSame('a-1', $dto->items[0]->sku);
        $this->assertSame(5, $dto->items[1]->quantity);
        $this->assertNotNull($dto->scheduledAt);
        $this->assertSame('2026-08-07 10:30:00', $dto->scheduledAt->format('Y-m-d H:i:s'));
    }

    public function testEmptyObjectGivesRequiredErrorsNotTypeObject(): void
    {
        try {
            $this->map(['title' => 'order', 'address' => []]);
        } catch (PayloadViolationException $payloadViolationException) {
            $violation = $payloadViolationException->getViolations()->all()[0];
            $this->assertSame('required', $violation->code);
            $this->assertSame('body/address/city', $violation->pointer);

            return;
        }

        $this->fail('Expected PayloadViolationException.');
    }

    public function testBrokenCollectionItemPointsToElementField(): void
    {
        try {
            $this->map([
                'title' => 'order',
                'address' => ['city' => 'Yerevan', 'zip' => '0010'],
                'items' => [
                    ['sku' => 'a-1', 'quantity' => 2],
                    ['sku' => 'b-2', 'quantity' => 'abc'],
                ],
            ]);
        } catch (PayloadViolationException $payloadViolationException) {
            $violation = $payloadViolationException->getViolations()->all()[0];
            $this->assertSame(Response::HTTP_BAD_REQUEST, $payloadViolationException->getStatusCode());
            $this->assertSame('body/items/1/quantity', $violation->pointer);
            $this->assertSame('type.int', $violation->code);

            return;
        }

        $this->fail('Expected PayloadViolationException.');
    }

    public function testNestedValidatorViolationUsesSamePointerNotation(): void
    {
        try {
            $this->map([
                'title' => 'order',
                'address' => ['city' => 'Yerevan', 'zip' => '0010'],
                'items' => [
                    ['sku' => 'a-1', 'quantity' => -5],
                ],
            ]);
        } catch (PayloadViolationException $payloadViolationException) {
            $violation = $payloadViolationException->getViolations()->all()[0];
            $this->assertSame(Response::HTTP_BAD_REQUEST, $payloadViolationException->getStatusCode());
            $this->assertSame('body/items/0/quantity', $violation->pointer);

            return;
        }

        $this->fail('Expected PayloadViolationException.');
    }

    public function testNonObjectNestedValueIsTypeError(): void
    {
        try {
            $this->map(['title' => 'order', 'address' => 'not-an-object']);
        } catch (PayloadViolationException $payloadViolationException) {
            $violation = $payloadViolationException->getViolations()->all()[0];
            $this->assertSame('type.object', $violation->code);
            $this->assertSame('body/address', $violation->pointer);

            return;
        }

        $this->fail('Expected PayloadViolationException.');
    }

    public function testAssociativeArrayInsteadOfListIsTypeError(): void
    {
        try {
            $this->map([
                'title' => 'order',
                'address' => ['city' => 'Yerevan', 'zip' => '0010'],
                'items' => ['a' => ['sku' => 'a-1', 'quantity' => 2]],
            ]);
        } catch (PayloadViolationException $payloadViolationException) {
            $violation = $payloadViolationException->getViolations()->all()[0];
            $this->assertSame('type.collection', $violation->code);
            $this->assertSame('body/items', $violation->pointer);

            return;
        }

        $this->fail('Expected PayloadViolationException.');
    }

    public function testOversizedCollectionIsRejectedBeforeConstructingItems(): void
    {
        $items = [];
        for ($i = 0; $i <= 1000; ++$i) {
            $items[] = ['sku' => 'sku-' . $i, 'quantity' => 1];
        }

        try {
            $this->map([
                'title' => 'order',
                'address' => ['city' => 'Yerevan', 'zip' => '0010'],
                'items' => $items,
            ]);
        } catch (PayloadViolationException $payloadViolationException) {
            $violation = $payloadViolationException->getViolations()->all()[0];
            $this->assertSame('collection.too_many', $violation->code);
            $this->assertSame('body/items', $violation->pointer);

            return;
        }

        $this->fail('Expected PayloadViolationException.');
    }

    public function testMissingRequiredFieldsAccumulateWithinObject(): void
    {
        try {
            // required — не «битое» поле: отсутствующие поля объекта перечисляются все,
            // fail-fast (break) относится только к ошибкам каста.
            $this->map(['title' => 'order', 'address' => ['unexpected' => 1]]);
        } catch (PayloadViolationException $payloadViolationException) {
            $violations = $payloadViolationException->getViolations()->all();
            $this->assertCount(2, $violations);
            $this->assertSame('body/address/city', $violations[0]->pointer);
            $this->assertSame('body/address/zip', $violations[1]->pointer);
            $this->assertSame('required', $violations[0]->code);

            return;
        }

        $this->fail('Expected PayloadViolationException.');
    }

    public function testFailFastOnTwoCastBrokenNestedFieldsReportsSingleError(): void
    {
        try {
            $this->map([
                'title' => 'order',
                'address' => ['city' => ['broken'], 'zip' => ['also-broken']],
            ]);
        } catch (PayloadViolationException $payloadViolationException) {
            $this->assertCount(1, $payloadViolationException->getViolations());
            $this->assertSame('body/address/city', $payloadViolationException->getViolations()->all()[0]->pointer);
            $this->assertSame('type.string', $payloadViolationException->getViolations()->all()[0]->code);

            return;
        }

        $this->fail('Expected PayloadViolationException.');
    }

    private function map(array $body): SampleNestedRequest
    {
        $request = Request::create(
            uri: '/orders',
            method: Request::METHOD_POST,
            content: (string)json_encode($body),
        );

        return $this->mapper()->map(SampleNestedRequest::class, $request);
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
