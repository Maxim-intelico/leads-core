<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload\Invalid;

use Leads\Core\Payload\Attribute\FromQuery;

/**
 * Используется в тестах как ВЛОЖЕННЫЙ DTO: атрибут источника внутри — ошибка построения плана.
 */
final readonly class AddressWithSourceAttribute
{
    public function __construct(
        #[FromQuery]
        public string $city,
    ) {
    }
}
