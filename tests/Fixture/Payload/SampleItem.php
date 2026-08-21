<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class SampleItem
{
    public function __construct(
        public string $sku,
        #[Assert\Positive]
        public int $quantity,
    ) {
    }
}
