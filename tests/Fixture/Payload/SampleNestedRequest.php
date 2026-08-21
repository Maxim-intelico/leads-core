<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload;

use Leads\Core\Payload\Attribute\FromBody;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Фикстура механизма: вложенный объект + коллекция + datetime из тела.
 */
final readonly class SampleNestedRequest
{
    /**
     * @param list<SampleItem> $items
     */
    public function __construct(
        #[FromBody]
        public string $title,
        #[FromBody]
        #[Valid]
        public SampleAddress $address,
        #[FromBody]
        #[Valid]
        public array $items = [],
        #[FromBody]
        public ?\DateTimeImmutable $scheduledAt = null,
        #[FromBody]
        public ?float $totalPrice = null,
    ) {
    }
}
