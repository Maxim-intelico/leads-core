<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Error;

use Leads\Core\Payload\Error\PayloadExceptionListener;
use Leads\Core\Payload\Error\PayloadViolationException;
use Leads\Core\Payload\Error\StateConflictException;
use Leads\Core\Payload\Error\Violation;
use Leads\Core\Payload\Error\ViolationList;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class PayloadExceptionListenerTest extends TestCase
{
    public function testRendersValidationProblemJson(): void
    {
        $exception = PayloadViolationException::fromCast(new ViolationList([
            new Violation('body/items/0/quantity', 'Expected an integer.', 'type.int'),
        ]));

        $event = $this->event($exception);

        new PayloadExceptionListener()->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));

        $body = json_decode((string)$response->getContent(), true);
        $this->assertSame('/errors/validation', $body['type']);
        $this->assertSame('Request payload is invalid', $body['title']);
        $this->assertSame(400, $body['status']);
        $this->assertIsList($body['errors']);
        $this->assertSame(
            ['pointer' => 'body/items/0/quantity', 'detail' => 'Expected an integer.', 'code' => 'type.int'],
            $body['errors'][0],
        );
    }

    public function testRendersConflictWith409(): void
    {
        $event = $this->event(new StateConflictException('body/email', 'Email is already taken.'));

        new PayloadExceptionListener()->onKernelException($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(409, $response->getStatusCode());

        $body = json_decode((string)$response->getContent(), true);
        $this->assertSame('body/email', $body['errors'][0]['pointer']);
        $this->assertSame('state.conflict', $body['errors'][0]['code']);
    }

    public function testValidatorViolationRendersAs400(): void
    {
        $exception = PayloadViolationException::fromValidator(new ViolationList([
            new Violation('query/toDate', 'fromDate must be before or equal to toDate.', 'validation.failed'),
        ]));

        $event = $this->event($exception);

        new PayloadExceptionListener()->onKernelException($event);

        $this->assertNotNull($event->getResponse());
        $this->assertSame(400, $event->getResponse()->getStatusCode());
    }

    public function testIgnoresForeignExceptions(): void
    {
        $event = $this->event(new \RuntimeException('unrelated'));

        new PayloadExceptionListener()->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    private function event(\Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/sample'),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }
}
