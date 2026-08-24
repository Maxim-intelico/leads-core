<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload\Invalid;

use Leads\Core\Payload\Attribute\FromBody;

final readonly class NullableItemCollectionRequest
{
    /**
     * @param list<?string> $tags
     */
    public function __construct(
        #[FromBody]
        public array $tags,
    ) {
    }
}
