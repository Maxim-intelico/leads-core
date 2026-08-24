<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload;

use Leads\Core\Payload\Error\MalformedPayloadException;
use Leads\Core\Payload\Error\PayloadTooLargeException;
use Leads\Core\Payload\RequestContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RequestContextTest extends TestCase
{
    public function testEmptyBodyDecodesToEmptyArray(): void
    {
        $context = $this->contextWithBody('');

        $this->assertSame([], $context->body());
    }

    public function testValidJsonObjectIsDecoded(): void
    {
        $context = $this->contextWithBody('{"name": "test", "items": [1, 2]}');

        $this->assertSame(['name' => 'test', 'items' => [1, 2]], $context->body());
    }

    public function testOversizedBodyIsRejectedBeforeDecode(): void
    {
        $context = $this->contextWithBody('{"a":"' . str_repeat('x', 100) . '"}', maxBodyBytes: 50);

        $this->expectException(PayloadTooLargeException::class);

        $context->body();
    }

    public function testOversizedInvalidJsonStillGives413Not400(): void
    {
        $context = $this->contextWithBody(str_repeat('не json', 100), maxBodyBytes: 50);

        $this->expectException(PayloadTooLargeException::class);

        $context->body();
    }

    public function testMalformedJsonIsRejected(): void
    {
        $context = $this->contextWithBody('{"name": broken}');

        $this->expectException(MalformedPayloadException::class);

        $context->body();
    }

    public function testTooDeepJsonIsRejected(): void
    {
        $body = str_repeat('[', 40) . '1' . str_repeat(']', 40);
        $context = $this->contextWithBody($body);

        $this->expectException(MalformedPayloadException::class);

        $context->body();
    }

    public function testScalarJsonBodyIsRejected(): void
    {
        $context = $this->contextWithBody('42');

        $this->expectException(MalformedPayloadException::class);

        $context->body();
    }

    public function testQueryAndPathParamsAndHeader(): void
    {
        $request = Request::create('/users?limit=10', Request::METHOD_GET);
        $request->attributes->set('_route_params', ['id' => 'uuid-1']);
        $request->attributes->set('_route', 'users_list');
        $request->headers->set('X-Request-Source', 'mobile');

        $context = RequestContext::fromRequest($request, 1024);

        $this->assertSame(['limit' => '10'], $context->query());
        $this->assertSame(['id' => 'uuid-1'], $context->pathParams());
        $this->assertSame('mobile', $context->header('X-Request-Source'));
        $this->assertNull($context->header('X-Missing'));
        $this->assertSame('users_list', $context->routeName());
    }

    private function contextWithBody(string $body, int $maxBodyBytes = 1024): RequestContext
    {
        $request = Request::create('/users', Request::METHOD_POST, server: [], content: $body);

        return RequestContext::fromRequest($request, $maxBodyBytes);
    }
}
