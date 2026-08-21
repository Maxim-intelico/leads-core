<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Integration\Payload;

use Leads\Core\Payload\Error\PayloadViolationException;
use Leads\Core\Payload\PayloadMapper;
use Leads\Core\Tests\Fixture\Payload\SampleNestedRequest;
use Leads\Core\Tests\Fixture\Payload\SampleRequest;
use Leads\Core\Tests\Fixture\Payload\SampleSortField;
use Leads\Core\Tests\Integration\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Сквозная проверка DI-wiring бандла: маппер из контейнера со всеми tagged-кастерами
 * и источниками собирает фикстуры и отдаёт указатели в формате контракта.
 */
final class PayloadWiringTest extends KernelTestCase
{
    public function testFlatFixtureIsMappedThroughContainerWiredMapper(): void
    {
        $mapper = $this->mapper();

        $request = Request::create('/sample?sortBy=LASTNAME&limit=500&email=%20%20');
        $request->headers->set('X-Request-Source', 'mobile');
        $request->attributes->set('_route_params', ['projectId' => 'uuid-42']);

        $dto = $mapper->map(SampleRequest::class, $request);

        $this->assertInstanceOf(SampleRequest::class, $dto);
        $this->assertSame(SampleSortField::LastName, $dto->sortBy);
        $this->assertSame(100, $dto->limit);
        $this->assertNull($dto->email);
        $this->assertSame('mobile', $dto->requestSource);
        $this->assertSame('uuid-42', $dto->projectId);
    }

    public function testNestedFixtureIsMappedThroughContainerWiredMapper(): void
    {
        $mapper = $this->mapper();

        $request = Request::create(
            uri: '/orders',
            method: Request::METHOD_POST,
            content: (string)json_encode([
                'title' => 'order',
                'address' => ['city' => 'Yerevan', 'zip' => '0010'],
                'items' => [['sku' => 'a-1', 'quantity' => 2]],
            ]),
        );

        $dto = $mapper->map(SampleNestedRequest::class, $request);

        $this->assertInstanceOf(SampleNestedRequest::class, $dto);
        $this->assertSame('Yerevan', $dto->address->city);
        $this->assertCount(1, $dto->items);
        $this->assertSame(2, $dto->items[0]->quantity);
    }

    public function testErrorPointerFormatSurvivesContainerWiring(): void
    {
        $mapper = $this->mapper();

        $request = Request::create(
            uri: '/orders',
            method: Request::METHOD_POST,
            content: (string)json_encode([
                'title' => 'order',
                'address' => ['city' => 'Yerevan', 'zip' => '0010'],
                'items' => [['sku' => 'a-1', 'quantity' => 'abc']],
            ]),
        );

        try {
            $mapper->map(SampleNestedRequest::class, $request);
        } catch (PayloadViolationException $payloadViolationException) {
            $this->assertSame('body/items/0/quantity', $payloadViolationException->getViolations()->all()[0]->pointer);

            return;
        }

        $this->fail('Expected PayloadViolationException.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // FrameworkBundle::boot() регистрирует exception handler и не снимает его —
        // без restore PHPUnit помечает тест как risky.
        restore_exception_handler();
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    private function mapper(): PayloadMapper
    {
        self::bootKernel();

        $mapper = self::getContainer()->get('leads_core.test.payload_mapper');
        $this->assertInstanceOf(PayloadMapper::class, $mapper);

        return $mapper;
    }
}
