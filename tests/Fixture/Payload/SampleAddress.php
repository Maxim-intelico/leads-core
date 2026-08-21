<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload;

/**
 * Вложенный DTO: атрибутов источника нет намеренно — значения приходят из массива родителя.
 */
final readonly class SampleAddress
{
    public function __construct(
        public string $city,
        public string $zip,
    ) {
    }
}
