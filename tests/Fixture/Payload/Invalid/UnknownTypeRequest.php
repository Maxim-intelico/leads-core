<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload\Invalid;

use Leads\Core\Payload\Attribute\FromQuery;

final readonly class UnknownTypeRequest
{
    public function __construct(
        #[FromQuery]
        public iterable $items,
    ) {
    }
}
