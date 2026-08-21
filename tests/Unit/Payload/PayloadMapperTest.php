<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload;

use Leads\Core\Payload\Cast\BoolCaster;
use Leads\Core\Payload\Cast\CasterRegistry;
use Leads\Core\Payload\Cast\DateCaster;
use Leads\Core\Payload\Cast\EnumCaster;
use Leads\Core\Payload\Cast\IntCaster;
use Leads\Core\Payload\Cast\ScalarCaster;
use Leads\Core\Payload\Error\MalformedPayloadException;
use Leads\Core\Payload\Error\PayloadExceptionListener;
use Leads\Core\Payload\Error\PayloadTooLargeException;
use Leads\Core\Payload\Error\PayloadViolationException;
use Leads\Core\Payload\ObjectAssembler;
use Leads\Core\Payload\PayloadMapper;
use Leads\Core\Payload\Plan\PlanFactory;
use Leads\Core\Payload\Source\BodySource;
use Leads\Core\Payload\Source\HeaderSource;
use Leads\Core\Payload\Source\PathSource;
use Leads\Core\Payload\Source\QuerySource;
use Leads\Core\Tests\Fixture\Payload\SampleRequest;
use Leads\Core\Tests\Fixture\Payload\SampleSortField;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\Validation;

final class PayloadMapperTest extends TestCase
{
    #[DataProvider('successCases')]
    public function testSuccessfulMapping(string $query, SampleSortField $sortBy, int $limit, ?string $email): void
    {
        $dto = $this->map($query);

        $this->assertSame($sortBy, $dto->sortBy);
        $this->assertSame($limit, $dto->limit);
        $this->assertSame($email, $dto->email);
    }

    public static function successCases(): iterable
    {
        yield 'defaults' => ['', SampleSortField::CreatedAt, 20, null];
        yield 'case insensitive enum' => ['sortBy=LASTNAME', SampleSortField::LastName, 20, null];
        yield 'unknown enum value falls back' => ['sortBy=password', SampleSortField::CreatedAt, 20, null];
        yield 'limit clamped down' => ['limit=500', SampleSortField::CreatedAt, 100, null];
        yield 'limit clamped up' => ['limit=0', SampleSortField::CreatedAt, 1, null];
        yield 'blank email is null' => ['email=', SampleSortField::CreatedAt, 20, null];
        yield 'spaces-only email is null' => ['email=%20%20', SampleSortField::CreatedAt, 20, null];
        yield 'unicode padding trimmed' => [
            'email=%C2%A0john%40x.com%C2%A0',
            SampleSortField::CreatedAt,
            20,
            'john@x.com',
        ];
    }

    public function testDateIsParsedWithZeroedTime(): void
    {
        $dto = $this->map('fromDate=2026-08-07');

        $this->assertNotNull($dto->fromDate);
        $this->assertSame('2026-08-07 00:00:00', $dto->fromDate->format('Y-m-d H:i:s'));
    }

    public function testBoolAndPathAndHeaderSources(): void
    {
        $request = Request::create('/sample?archived=true');
        $request->attributes->set('_route_params', ['projectId' => 'uuid-42']);
        $request->headers->set('X-Request-Source', 'mobile');

        $dto = $this->mapper()->map(SampleRequest::class, $request);

        $this->assertTrue($dto->archived);
        $this->assertSame('uuid-42', $dto->projectId);
        $this->assertSame('mobile', $dto->requestSource);
    }

    #[DataProvider('errorCases')]
    public function testMappingErrors(string $query, string $pointer, int $status): void
    {
        try {
            $this->map($query);
        } catch (PayloadViolationException $payloadViolationException) {
            $this->assertSame($status, $payloadViolationException->getStatusCode());
            $this->assertCount(1, $payloadViolationException->getViolations());
            $this->assertSame($pointer, $payloadViolationException->getViolations()->all()[0]->pointer);

            return;
        }

        $this->fail(sprintf('Expected PayloadViolationException for query "%s".', $query));
    }

    public static function errorCases(): iterable
    {
        yield 'wrong date format' => ['fromDate=07.08.2026', 'query/fromDate', Response::HTTP_BAD_REQUEST];
        yield 'impossible date' => ['fromDate=2026-02-31', 'query/fromDate', Response::HTTP_BAD_REQUEST];
        yield 'inverted range' => [
            'fromDate=2026-08-07&toDate=2026-08-01',
            'query/toDate',
            Response::HTTP_BAD_REQUEST,
        ];
        yield 'array instead of scalar not restored by fallback' => [
            'sortBy%5B%5D=id&sortBy%5B%5D=email',
            'query/sortBy',
            Response::HTTP_BAD_REQUEST,
        ];
        yield 'bad limit' => ['limit=abc', 'query/limit', Response::HTTP_BAD_REQUEST];
    }

    public function testFailFastReportsOnlyFirstBrokenFieldInConstructorOrder(): void
    {
        try {
            $this->map('limit=abc&fromDate=07.08.2026');
        } catch (PayloadViolationException $payloadViolationException) {
            $this->assertCount(1, $payloadViolationException->getViolations());
            $this->assertSame('query/limit', $payloadViolationException->getViolations()->all()[0]->pointer);

            return;
        }

        $this->fail('Expected PayloadViolationException.');
    }

    public function testOversizedBodyIsRejectedWith413(): void
    {
        $request = Request::create(
            uri: '/sample',
            method: Request::METHOD_POST,
            content: '{"note":"' . str_repeat('x', 3000) . '"}',
        );

        $this->expectException(PayloadTooLargeException::class);

        $this->mapper()->map(SampleRequest::class, $request);
    }

    public function testMalformedBodyIsRejectedWith400(): void
    {
        $request = Request::create(uri: '/sample', method: Request::METHOD_POST, content: '{"note": broken}');

        $this->expectException(MalformedPayloadException::class);

        $this->mapper()->map(SampleRequest::class, $request);
    }

    public function testUnknownBodyFieldIsLoggedByNameWithoutValue(): void
    {
        $logger = new RecordingLogger();
        $request = Request::create(
            uri: '/sample',
            method: Request::METHOD_POST,
            content: '{"password": "hunter2"}',
        );
        $request->attributes->set('_route', 'sample_route');

        $this->mapper($logger)->map(SampleRequest::class, $request);

        $this->assertCount(1, $logger->records);
        $this->assertSame('payload.unknown_fields', $logger->records[0]['message']);
        $this->assertSame(['password'], $logger->records[0]['context']['fields']);
        $this->assertSame('sample_route', $logger->records[0]['context']['route']);
        $this->assertStringNotContainsString('hunter2', (string)json_encode($logger->records[0]));
    }

    public function testSecretFromRequestNeverAppearsInErrorResponse(): void
    {
        $logger = new RecordingLogger();
        $request = Request::create(
            uri: '/sample?limit=abc',
            method: Request::METHOD_POST,
            content: '{"password": "hunter2"}',
        );

        try {
            $this->mapper($logger)->map(SampleRequest::class, $request);
            $this->fail('Expected PayloadViolationException.');
        } catch (PayloadViolationException $payloadViolationException) {
            $event = new ExceptionEvent(
                $this->createStub(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST,
                $payloadViolationException,
            );
            new PayloadExceptionListener()->onKernelException($event);

            $this->assertNotNull($event->getResponse());
            $this->assertStringNotContainsString('hunter2', (string)$event->getResponse()->getContent());
            $this->assertStringNotContainsString('hunter2', (string)json_encode($logger->records));
        }
    }

    public function testKnownQueryIsNotReportedAsUnknown(): void
    {
        $logger = new RecordingLogger();

        $this->mapper($logger)->map(SampleRequest::class, Request::create('/sample?limit=5&utm_source=newsletter'));

        $this->assertSame([], $logger->records);
    }

    private function map(string $query): SampleRequest
    {
        return $this->mapper()->map(SampleRequest::class, Request::create('/sample?' . $query));
    }

    private function mapper(?LoggerInterface $logger = null): PayloadMapper
    {
        $registry = new CasterRegistry([
            new ScalarCaster(),
            new IntCaster(),
            new BoolCaster(),
            new EnumCaster(),
            new DateCaster(),
        ]);

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        return new PayloadMapper(
            plans: new PlanFactory($registry),
            assembler: new ObjectAssembler(),
            sources: [
                'body' => new BodySource(),
                'query' => new QuerySource(),
                'path' => new PathSource(),
                'header' => new HeaderSource(),
            ],
            validator: $validator,
            logger: $logger ?? new NullLogger(),
        );
    }
}
