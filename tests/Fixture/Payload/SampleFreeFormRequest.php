<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Fixture\Payload;

use Leads\Core\Payload\Attribute\FreeForm;
use Leads\Core\Payload\Attribute\FromBody;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Фикстура механизма: произвольный JSON как есть (#[FreeForm]).
 * Count на options пиннит, что констрейнты валидатора работают после сборки.
 */
final readonly class SampleFreeFormRequest
{
    public function __construct(
        #[FromBody]
        #[FreeForm]
        #[Assert\Count(max: 5)]
        public array $options = [],
        #[FromBody]
        #[FreeForm]
        public ?array $meta = null,
    ) {
    }
}
