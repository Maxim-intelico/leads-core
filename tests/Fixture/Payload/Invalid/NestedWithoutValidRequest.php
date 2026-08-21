<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload\Invalid;

use Leads\Core\Payload\Attribute\FromBody;
use Leads\Core\Tests\Fixture\Payload\SampleItem;

/**
 * У SampleItem есть констрейнт (Assert\Positive) — без Assert\Valid он молча
 * не выполнялся бы, поэтому план обязан не собраться.
 */
final readonly class NestedWithoutValidRequest
{
    public function __construct(
        #[FromBody]
        public SampleItem $item,
    ) {
    }
}
