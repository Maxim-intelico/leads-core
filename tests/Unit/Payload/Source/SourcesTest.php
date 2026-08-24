<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Source;

use Leads\Core\Payload\RequestContext;
use Leads\Core\Payload\Source\BodySource;
use Leads\Core\Payload\Source\HeaderSource;
use Leads\Core\Payload\Source\PathSource;
use Leads\Core\Payload\Source\QuerySource;
use Leads\Core\Tests\Builder\PropertyPlanBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class SourcesTest extends TestCase
{
    public function testBodySourceDistinguishesAbsentFromNull(): void
    {
        $context = $this->context();
        $present = new PropertyPlanBuilder(key: 'name', sourceKey: 'body')->build();
        $explicitNull = new PropertyPlanBuilder(key: 'comment', sourceKey: 'body')->build();
        $absent = new PropertyPlanBuilder(key: 'missing', sourceKey: 'body')->build();

        $source = new BodySource();

        $this->assertTrue($source->has($context, $present));
        $this->assertSame('john', $source->extract($context, $present));
        $this->assertTrue($source->has($context, $explicitNull));
        $this->assertNull($source->extract($context, $explicitNull));
        $this->assertFalse($source->has($context, $absent));
    }

    public function testQuerySource(): void
    {
        $context = $this->context();
        $present = new PropertyPlanBuilder(key: 'limit')->build();
        $absent = new PropertyPlanBuilder(key: 'missing')->build();

        $source = new QuerySource();

        $this->assertTrue($source->has($context, $present));
        $this->assertSame('10', $source->extract($context, $present));
        $this->assertFalse($source->has($context, $absent));
    }

    public function testPathSource(): void
    {
        $context = $this->context();
        $present = new PropertyPlanBuilder(key: 'id', sourceKey: 'path')->build();
        $absent = new PropertyPlanBuilder(key: 'missing', sourceKey: 'path')->build();

        $source = new PathSource();

        $this->assertTrue($source->has($context, $present));
        $this->assertSame('uuid-1', $source->extract($context, $present));
        $this->assertFalse($source->has($context, $absent));
    }

    public function testHeaderSource(): void
    {
        $context = $this->context();
        $present = new PropertyPlanBuilder(key: 'X-Request-Source', sourceKey: 'header')->build();
        $absent = new PropertyPlanBuilder(key: 'X-Missing', sourceKey: 'header')->build();

        $source = new HeaderSource();

        $this->assertTrue($source->has($context, $present));
        $this->assertSame('mobile', $source->extract($context, $present));
        $this->assertFalse($source->has($context, $absent));
    }

    public function testSourceKeysAreDistinct(): void
    {
        $this->assertSame('body', BodySource::key());
        $this->assertSame('query', QuerySource::key());
        $this->assertSame('path', PathSource::key());
        $this->assertSame('header', HeaderSource::key());
    }

    private function context(): RequestContext
    {
        $request = Request::create(
            uri: '/users?limit=10',
            method: Request::METHOD_POST,
            content: '{"name": "john", "comment": null}',
        );
        $request->attributes->set('_route_params', ['id' => 'uuid-1']);
        $request->headers->set('X-Request-Source', 'mobile');

        return RequestContext::fromRequest($request, 1024);
    }
}
