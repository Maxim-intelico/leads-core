<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload\Invalid;

use Leads\Core\Payload\Attribute\FromBody;
use Leads\Core\Payload\Attribute\MaxItems;

final readonly class MaxItemsBelowOneRequest
{
    /**
     * @param list<string> $ids
     */
    public function __construct(
        #[FromBody]
        #[MaxItems(0)]
        public array $ids,
    ) {
    }
}
