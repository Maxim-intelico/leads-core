<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast;

use Leads\Core\Payload\Error\Reason;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Payload\Result;

/**
 * Строки и float. Целые и bool — отдельные кастеры со своими правилами.
 */
final class ScalarCaster implements ValueCaster
{
    public function supports(PropertyPlan $plan): bool
    {
        return $plan->type === 'string' || $plan->type === 'float';
    }

    public function cast(RawValue $raw, PropertyPlan $plan): Result
    {
        if ($plan->type === 'float') {
            return $this->castFloat($raw->value);
        }

        return $this->castString($raw->value);
    }

    /**
     * @return Result<string>
     */
    private function castString(mixed $value): Result
    {
        if (\is_string($value)) {
            return Result::ok($value);
        }

        if (\is_int($value) || \is_float($value)) {
            return Result::ok((string)$value);
        }

        return Result::fail(new Reason('type.string', 'Expected a string.'));
    }

    /**
     * @return Result<float>
     */
    private function castFloat(mixed $value): Result
    {
        if (\is_float($value) || \is_int($value)) {
            return Result::ok((float)$value);
        }

        if (\is_string($value) && $value !== '') {
            $filtered = filter_var($value, \FILTER_VALIDATE_FLOAT);
            if ($filtered !== false) {
                return Result::ok($filtered);
            }
        }

        return Result::fail(new Reason('type.float', 'Expected a number.'));
    }
}
