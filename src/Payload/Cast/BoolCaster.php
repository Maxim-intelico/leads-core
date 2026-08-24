<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast;

use Leads\Core\Payload\Error\Reason;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Payload\Result;

final class BoolCaster implements ValueCaster
{
    public function supports(PropertyPlan $plan): bool
    {
        return $plan->type === 'bool';
    }

    public function cast(RawValue $raw, PropertyPlan $plan): Result
    {
        $value = $raw->value;

        if (\is_bool($value)) {
            return Result::ok($value);
        }

        if (\is_string($value) || \is_int($value)) {
            $filtered = filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);
            if ($filtered !== null) {
                return Result::ok($filtered);
            }
        }

        return Result::fail(new Reason('type.bool', 'Expected a boolean.'));
    }
}
