<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast\Decorator;

use Leads\Core\Payload\Cast\ValueCaster;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Payload\Result;

final readonly class ClampCaster implements ValueCaster
{
    public function __construct(
        private ValueCaster $inner,
        private int $min,
        private int $max,
    ) {
    }

    public function supports(PropertyPlan $plan): bool
    {
        return $this->inner->supports($plan);
    }

    public function cast(RawValue $raw, PropertyPlan $plan): Result
    {
        $result = $this->inner->cast($raw, $plan);

        if ($result->isOk() === false) {
            return $result;
        }

        $value = $result->value();

        if (\is_int($value) === false) {
            return $result;
        }

        return Result::ok(min(max($value, $this->min), $this->max));
    }
}
