<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Error;

use Leads\Core\Payload\Error\Reason;
use Leads\Core\Payload\Error\Violation;
use PHPUnit\Framework\TestCase;

final class ReasonTest extends TestCase
{
    public function testNestPrependsSegmentToEmptyPath(): void
    {
        $reason = new Reason('type.int', 'Expected an integer.');

        $nested = $reason->nest('quantity');

        $this->assertSame('quantity', $nested->path);
    }

    public function testNestAccumulatesPathBottomUp(): void
    {
        $reason = new Reason('type.int', 'Expected an integer.');

        $nested = $reason->nest('quantity')->nest(0)->nest('body/items');

        $this->assertSame('body/items/0/quantity', $nested->path);
    }

    public function testNestKeepsCodeDetailAndParams(): void
    {
        $reason = new Reason('enum.unknown', 'Unknown value.', ['allowed' => 'a, b']);

        $nested = $reason->nest('sortBy');

        $this->assertSame('enum.unknown', $nested->code);
        $this->assertSame('Unknown value.', $nested->detail);
        $this->assertSame(['allowed' => 'a, b'], $nested->params);
    }

    public function testViolationFromReasonUsesPathAsPointer(): void
    {
        $reason = new Reason('type.int', 'Expected an integer.', path: 'body/items/0/quantity');

        $violation = Violation::fromReason($reason);

        $this->assertSame('body/items/0/quantity', $violation->pointer);
        $this->assertSame('Expected an integer.', $violation->detail);
        $this->assertSame('type.int', $violation->code);
    }
}
