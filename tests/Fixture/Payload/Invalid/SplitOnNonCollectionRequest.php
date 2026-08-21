<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload\Invalid;

use Leads\Core\Payload\Attribute\FromQuery;
use Leads\Core\Payload\Attribute\Split;

final readonly class SplitOnNonCollectionRequest
{
    public function __construct(
        #[FromQuery]
        #[Split]
        public string $ids,
    ) {
    }
}
