<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload\Invalid;

use Leads\Core\Payload\Attribute\FromQuery;
use Leads\Core\Payload\Attribute\MaxItems;

final readonly class MaxItemsOnNonCollectionRequest
{
    public function __construct(
        #[FromQuery]
        #[MaxItems(5)]
        public int $limit,
    ) {
    }
}
