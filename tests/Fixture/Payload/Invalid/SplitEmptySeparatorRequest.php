<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload\Invalid;

use Leads\Core\Payload\Attribute\FromQuery;
use Leads\Core\Payload\Attribute\Split;

final readonly class SplitEmptySeparatorRequest
{
    /**
     * @param list<string> $ids
     */
    public function __construct(
        #[FromQuery]
        #[Split(separator: '')]
        public array $ids = [],
    ) {
    }
}
