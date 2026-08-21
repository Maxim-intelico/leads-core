<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload;

use Leads\Core\Payload\Cast\BoolCaster;
use Leads\Core\Payload\Cast\CasterRegistry;
use Leads\Core\Payload\Cast\FreeFormCaster;
use Leads\Core\Payload\Cast\IntCaster;
use Leads\Core\Payload\Cast\ScalarCaster;
use Leads\Core\Payload\Error\PayloadViolationException;
use Leads\Core\Payload\ObjectAssembler;
use Leads\Core\Payload\PayloadMapper;
use Leads\Core\Payload\Plan\PlanFactory;
use Leads\Core\Payload\Source\BodySource;
use Leads\Core\Payload\Source\HeaderSource;
use Leads\Core\Payload\Source\PathSource;
use Leads\Core\Payload\Source\QuerySource;
use Leads\Core\Tests\Fixture\Payload\SampleFreeFormRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

final class PayloadMapperFreeFormTest extends TestCase
{
    public function testNestedStructurePassesVerbatim(): void
    {
        $options = ['a' => ['b' => 1], 'c' => [1, 2, ['d' => null]]];

        $dto = $this->map(['options' => $options]);

        $this->assertSame($options, $dto->options);
    }

    public function testScalarInsteadOfArrayIsTypeError(): void
    {
        try {
            $this->map(['options' => 'x']);
        } catch (PayloadViolationException $payloadViolationException) {
            $violation = $payloadViolationException->getViolations()->all()[0];
            $this->assertSame(Response::HTTP_BAD_REQUEST, $payloadViolationException->getStatusCode());
            $this->assertSame('type.array', $violation->code);
            $this->assertSame('body/options', $violation->pointer);

            return;
        }

        $this->fail('Expected PayloadViolationException.');
    }

    public function testNullForNullableFreeFormStaysNull(): void
    {
        $dto = $this->map(['meta' => null]);

        $this->assertNull($dto->meta);
    }

    public function testMissingKeysFallBackToDefaults(): void
    {
        $dto = $this->map([]);

        $this->assertSame([], $dto->options);
        $this->assertNull($dto->meta);
    }

    public function testValidatorConstraintsRunAfterMapping(): void
    {
        try {
            $this->map(['options' => ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5, 'f' => 6]]);
        } catch (PayloadViolationException $payloadViolationException) {
            $violation = $payloadViolationException->getViolations()->all()[0];
            $this->assertSame(Response::HTTP_BAD_REQUEST, $payloadViolationException->getStatusCode());
            $this->assertSame('body/options', $violation->pointer);

            return;
        }

        $this->fail('Expected PayloadViolationException.');
    }

    private function map(array $body): SampleFreeFormRequest
    {
        $request = Request::create(
            uri: '/samples',
            method: Request::METHOD_POST,
            content: (string)json_encode($body),
        );

        return $this->mapper()->map(SampleFreeFormRequest::class, $request);
    }

    private function mapper(): PayloadMapper
    {
        $assembler = new ObjectAssembler();
        $registry = new CasterRegistry([
            new ScalarCaster(),
            new IntCaster(),
            new BoolCaster(),
            new FreeFormCaster(),
        ]);

        return new PayloadMapper(
            plans: new PlanFactory($registry),
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
