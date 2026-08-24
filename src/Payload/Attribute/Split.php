<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Attribute;

/**
 * Коллекция скаляров, приходящая строкой с разделителем ('?ids=a,b,c').
 * Настоящий список ('?ids[]=a&ids[]=b') проходит без изменений — оба синтаксиса живут параллельно.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class Split
{
    public function __construct(
        public string $separator = ',',
    ) {
    }
}
