<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast\Decorator;

use Leads\Core\Payload\Cast\ValueCaster;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Payload\Result;

/**
 * null — это null. Пустая строка для ?string — тоже null: '?email=' значит «фильтр не задан»,
 * а не «email равен пустой строке». Правило живёт здесь, отдельного атрибута под это нет.
 */
final readonly class NullableCaster implements ValueCaster
{
    public function __construct(
        private ValueCaster $inner,
    ) {
    }

    public function supports(PropertyPlan $plan): bool
    {
        return $this->inner->supports($plan);
    }

    public function cast(RawValue $raw, PropertyPlan $plan): Result
    {
        $value = $raw->value;

        if ($value === null || ($plan->type === 'string' && \is_string($value) && trim($value) === '')) {
            return Result::ok(null);
        }

        return $this->inner->cast($raw, $plan);
    }
}
