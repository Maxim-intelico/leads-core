<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Resolver;

use Leads\Core\Payload\Attribute\Payload;
use Leads\Core\Payload\Cast\BoolCaster;
use Leads\Core\Payload\Cast\CasterRegistry;
use Leads\Core\Payload\Cast\DateCaster;
use Leads\Core\Payload\Cast\EnumCaster;
use Leads\Core\Payload\Cast\IntCaster;
use Leads\Core\Payload\Cast\ScalarCaster;
use Leads\Core\Payload\ObjectAssembler;
use Leads\Core\Payload\PayloadMapper;
use Leads\Core\Payload\Plan\PlanFactory;
use Leads\Core\Payload\Resolver\PayloadValueResolver;
use Leads\Core\Payload\Source\BodySource;
use Leads\Core\Payload\Source\HeaderSource;
use Leads\Core\Payload\Source\PathSource;
use Leads\Core\Payload\Source\QuerySource;
use Leads\Core\Tests\Fixture\Payload\SampleRequest;
use Leads\Core\Tests\Fixture\Payload\SampleSortField;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Validation;

final class PayloadValueResolverTest extends TestCase
{
    public function testResolvesDtoForArgumentWithPayloadAttribute(): void
    {
        $argument = new ArgumentMetadata(
            name: 'request',
            type: SampleRequest::class,
            isVariadic: false,
            hasDefaultValue: false,
            defaultValue: null,
            isNullable: false,
            attributes: [new Payload()],
        );

        $resolved = [...$this->resolver()->resolve(Request::create('/sample?sortBy=LASTNAME'), $argument)];

        $this->assertCount(1, $resolved);
        $this->assertInstanceOf(SampleRequest::class, $resolved[0]);
        $this->assertSame(SampleSortField::LastName, $resolved[0]->sortBy);
    }

    public function testSkipsArgumentWithoutPayloadAttribute(): void
    {
        $argument = new ArgumentMetadata(
            name: 'request',
            type: SampleRequest::class,
            isVariadic: false,
            hasDefaultValue: false,
            defaultValue: null,
        );

        $resolved = [...$this->resolver()->resolve(Request::create('/sample'), $argument)];

        $this->assertSame([], $resolved);
    }

    public function testThrowsOnUntypedArgumentWithPayloadAttribute(): void
    {
        $argument = new ArgumentMetadata(
            name: 'request',
            type: null,
            isVariadic: false,
            hasDefaultValue: false,
            defaultValue: null,
            isNullable: false,
            attributes: [new Payload()],
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must be typed');

        [...$this->resolver()->resolve(Request::create('/sample'), $argument)];
    }

    private function resolver(): PayloadValueResolver
    {
        $registry = new CasterRegistry([
            new ScalarCaster(),
            new IntCaster(),
            new BoolCaster(),
            new EnumCaster(),
            new DateCaster(),
        ]);

        $mapper = new PayloadMapper(
            plans: new PlanFactory($registry),
            assembler: new ObjectAssembler(),
            sources: [
                'body' => new BodySource(),
                'query' => new QuerySource(),
                'path' => new PathSource(),
                'header' => new HeaderSource(),
            ],
            validator: Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator(),
            logger: new NullLogger(),
        );

        return new PayloadValueResolver($mapper);
    }
}
