<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload\Invalid;

final readonly class MissingSourceRequest
{
    public function __construct(
        public int $limit,
    ) {
    }
}
