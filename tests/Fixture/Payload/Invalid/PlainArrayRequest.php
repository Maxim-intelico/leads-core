<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload\Invalid;

use Leads\Core\Payload\Attribute\FromBody;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class PlainArrayRequest
{
    public function __construct(
        #[FromBody]
        #[Assert\Valid]
        public array $items,
    ) {
    }
}
