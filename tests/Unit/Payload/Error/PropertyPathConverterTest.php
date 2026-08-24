<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Unit\Payload\Error;

use Leads\Core\Payload\Error\PropertyPathConverter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PropertyPathConverterTest extends TestCase
{
    #[DataProvider('cases')]
    public function testConvertsValidatorNotationToPointer(string $propertyPath, string $expected): void
    {
        $this->assertSame($expected, PropertyPathConverter::toPointer($propertyPath));
    }

    public static function cases(): iterable
    {
        yield 'collection element' => ['items[0].quantity', 'items/0/quantity'];
        yield 'nested object' => ['address.city', 'address/city'];
        yield 'plain property' => ['toDate', 'toDate'];
        yield 'deep nesting' => ['orders[2].items[0].sku', 'orders/2/items/0/sku'];
    }
}
