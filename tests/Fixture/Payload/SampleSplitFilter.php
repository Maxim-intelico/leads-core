<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload;

use Leads\Core\Payload\Attribute\Split;

/**
 * Вложенный фильтр с CSV-списком — воспроизводит легаси-форму '?filter[ids]=a,b,c'.
 */
final readonly class SampleSplitFilter
{
    /**
     * @param list<string> $ids
     */
    public function __construct(
        #[Split]
        public array $ids = [],
    ) {
    }
}
