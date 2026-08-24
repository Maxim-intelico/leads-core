<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload;

use Leads\Core\Payload\Attribute\FromBody;
use Leads\Core\Payload\Attribute\MaxItems;

final readonly class SampleMaxItemsRequest
{
    /**
     * @param list<string> $ids
     * @param list<string> $tags
     */
    public function __construct(
        #[FromBody]
        #[MaxItems(5)]
        public array $ids,
        #[FromBody]
        public array $tags = [],
    ) {
    }
}
