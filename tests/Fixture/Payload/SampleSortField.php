<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload;

/**
 * camelCase-кейс обязателен: ловит реализацию Normalized через strtoupper
 * ('LASTNAME' !== 'lastName' при сравнении с upper-кейсами).
 */
enum SampleSortField: string
{
    case CreatedAt = 'createdAt';
    case LastName = 'lastName';
    case Id = 'id';
}
