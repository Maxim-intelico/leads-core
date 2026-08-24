<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload;

use Leads\Core\Payload\Cast\CasterRegistry;
use Leads\Core\Payload\Cast\IntCaster;
use Leads\Core\Payload\Cast\ScalarCaster;
use Leads\Core\Payload\ObjectAssembler;
use Leads\Core\Payload\Plan\PlanFactory;
use Leads\Core\Tests\Fixture\Payload\SampleAddress;
use PHPUnit\Framework\TestCase;

final class ObjectAssemblerTest extends TestCase
{
    public function testAssemblesNestedPlanFromArray(): void
    {
        $plan = $this->factory()->build(SampleAddress::class, nested: true);

        $result = new ObjectAssembler()->assemble($plan, ['city' => 'Yerevan', 'zip' => '0010']);

        $this->assertTrue($result->isOk());
        $address = $result->value();
        $this->assertInstanceOf(SampleAddress::class, $address);
        $this->assertSame('Yerevan', $address->city);
    }

    public function testMissingRequiredFieldGivesRequiredReasonWithPropertyPointer(): void
    {
        $plan = $this->factory()->build(SampleAddress::class, nested: true);

        $result = new ObjectAssembler()->assemble($plan, ['city' => 'Yerevan']);

        $this->assertFalse($result->isOk());
        $this->assertSame('required', $result->reasons()[0]->code);
        $this->assertSame('zip', $result->reasons()[0]->path);
    }

    public function testExcessiveDepthIsRejected(): void
    {
        $plan = $this->factory()->build(SampleAddress::class, nested: true);

        $result = new ObjectAssembler()->assemble($plan, ['city' => 'Yerevan', 'zip' => '0010'], depth: 33);

        $this->assertFalse($result->isOk());
        $this->assertSame('depth.exceeded', $result->reasons()[0]->code);
    }

    private function factory(): PlanFactory
    {
        return new PlanFactory(new CasterRegistry([new ScalarCaster(), new IntCaster()]));
    }
}
