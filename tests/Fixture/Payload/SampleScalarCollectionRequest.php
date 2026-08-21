<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload;

use Leads\Core\Payload\Attribute\FromBody;
use Leads\Core\Payload\Attribute\Normalized;

/**
 * Фикстура механизма: коллекции скалярных элементов из тела.
 * Сознательно без Assert\Valid — скалярным коллекциям каскад не нужен.
 */
final readonly class SampleScalarCollectionRequest
{
    /**
     * @param list<string> $sourcesIds
     * @param list<int> $counts
     * @param list<SampleSortField> $sorts
     * @param list<\DateTimeImmutable> $dates
     * @param ?list<string> $tags
     */
    public function __construct(
        #[FromBody]
        public array $sourcesIds = [],
        #[FromBody]
        public array $counts = [],
        #[FromBody]
        #[Normalized]
        public array $sorts = [],
        #[FromBody]
        public array $dates = [],
        #[FromBody]
        public ?array $tags = null,
    ) {
    }
}
