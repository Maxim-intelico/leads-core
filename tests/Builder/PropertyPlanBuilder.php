<?php

declare(strict_types=1);

namespace Leads\Core\Tests\Builder;

use Leads\Core\Payload\Attribute\Clamp;
use Leads\Core\Payload\Attribute\Split;
use Leads\Core\Payload\Cast\ValueCaster;
use Leads\Core\Payload\Plan\PropertyPlan;

final class PropertyPlanBuilder
{
    public function __construct(
        public string $name = 'value',
        public string $key = 'value',
        public string $sourceKey = 'query',
        public string $pointer = 'query/value',
        public string $type = 'string',
        public bool $nullable = false,
        public bool $hasDefault = false,
        public bool $isStringLike = false,
        public ?string $enumClass = null,
        public ?string $nestedClass = null,
        public ?string $itemClass = null,
        public ?PropertyPlan $itemPlan = null,
        public int $maxItems = 1000,
        public ?Clamp $clamp = null,
        public ?Split $split = null,
        public bool $freeForm = false,
        public mixed $fallback = null,
        public bool $hasFallback = false,
        public bool $normalized = false,
        public ?ValueCaster $casterChain = null,
    ) {
    }

    public function build(): PropertyPlan
    {
        return new PropertyPlan(
            name: $this->name,
            key: $this->key,
            sourceKey: $this->sourceKey,
            pointer: $this->pointer,
            type: $this->type,
            nullable: $this->nullable,
            hasDefault: $this->hasDefault,
            isStringLike: $this->isStringLike,
            enumClass: $this->enumClass,
            nestedClass: $this->nestedClass,
            itemClass: $this->itemClass,
            itemPlan: $this->itemPlan,
            maxItems: $this->maxItems,
            clamp: $this->clamp,
            split: $this->split,
            freeForm: $this->freeForm,
            fallback: $this->fallback,
            hasFallback: $this->hasFallback,
            normalized: $this->normalized,
            casterChain: $this->casterChain,
        );
    }
}
