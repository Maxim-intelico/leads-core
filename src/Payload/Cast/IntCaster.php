<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast;

use Leads\Core\Payload\Error\Reason;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Payload\Result;

final class IntCaster implements ValueCaster
{
    public function supports(PropertyPlan $plan): bool
    {
        return $plan->type === 'int';
    }

    public function cast(RawValue $raw, PropertyPlan $plan): Result
    {
        $value = $raw->value;

        // '?limit=' даёт пустую строку: без явной ветки filter_var вернёт невнятную ошибку.
        if ($value === '' || $value === null) {
            return Result::fail(new Reason('type.int', 'Expected an integer.'));
        }

        if (\is_int($value)) {
            return Result::ok($value);
        }

        if (\is_string($value)) {
            $filtered = filter_var($value, \FILTER_VALIDATE_INT);
            if ($filtered !== false) {
                return Result::ok($filtered);
            }
        }

        return Result::fail(new Reason('type.int', 'Expected an integer.'));
    }
}
