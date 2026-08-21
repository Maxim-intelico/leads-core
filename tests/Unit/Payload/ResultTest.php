<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload;

use Leads\Core\Payload\Error\Reason;
use Leads\Core\Payload\Result;
use PHPUnit\Framework\TestCase;

final class ResultTest extends TestCase
{
    public function testOkHoldsValue(): void
    {
        $result = Result::ok(42);

        $this->assertTrue($result->isOk());
        $this->assertSame(42, $result->value());
        $this->assertSame([], $result->reasons());
    }

    public function testOkAcceptsNullValue(): void
    {
        $result = Result::ok(null);

        $this->assertTrue($result->isOk());
        $this->assertNull($result->value());
    }

    public function testFailHoldsReasons(): void
    {
        $first = new Reason('type.int', 'Expected an integer.');
        $second = new Reason('required', 'Required.');

        $result = Result::fail($first, $second);

        $this->assertFalse($result->isOk());
        $this->assertSame([$first, $second], $result->reasons());
    }

    public function testValueOnFailedResultThrows(): void
    {
        $result = Result::fail(new Reason('type.int', 'Expected an integer.'));

        $this->expectException(\LogicException::class);

        $result->value();
    }
}
