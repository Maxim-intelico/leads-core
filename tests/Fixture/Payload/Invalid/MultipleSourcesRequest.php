<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload\Invalid;

use Leads\Core\Payload\Attribute\FromBody;
use Leads\Core\Payload\Attribute\FromQuery;

final readonly class MultipleSourcesRequest
{
    public function __construct(
        #[FromQuery]
        #[FromBody]
        public string $name,
    ) {
    }
}
