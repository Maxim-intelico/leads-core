<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload\Invalid;

use Leads\Core\Payload\Attribute\FromBody;

final readonly class UnsupportedItemCollectionRequest
{
    /**
     * @param list<list<string>> $matrix
     */
    public function __construct(
        #[FromBody]
        public array $matrix,
    ) {
    }
}
