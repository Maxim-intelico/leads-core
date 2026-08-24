<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload;

use Leads\Core\Payload\Attribute\FromQuery;
use Leads\Core\Payload\Attribute\Split;

/**
 * Фикстура механизма: CSV-списки из query (#[Split]).
 */
final readonly class SampleSplitRequest
{
    /**
     * @param list<string> $ids
     * @param list<int> $counts
     * @param ?list<string> $tags
     */
    public function __construct(
        #[FromQuery]
        #[Split]
        public array $ids = [],
        #[FromQuery]
        #[Split(separator: '|')]
        public array $counts = [],
        #[FromQuery]
        #[Split]
        public ?array $tags = null,
        #[FromQuery]
        public ?SampleSplitFilter $filter = null,
    ) {
    }
}
