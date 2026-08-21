<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload\Invalid;

use Leads\Core\Payload\Attribute\FreeForm;
use Leads\Core\Payload\Attribute\FromBody;

final readonly class FreeFormOnNonArrayRequest
{
    public function __construct(
        #[FromBody]
        #[FreeForm]
        public string $options,
    ) {
    }
}
