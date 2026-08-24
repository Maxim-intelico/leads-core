<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast;

use Leads\Core\Payload\Error\Reason;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Payload\Result;

final class EnumCaster implements ValueCaster
{
    public function supports(PropertyPlan $plan): bool
    {
        return $plan->enumClass !== null;
    }

    public function cast(RawValue $raw, PropertyPlan $plan): Result
    {
        $enumClass = $plan->enumClass;

        if ($enumClass === null || is_subclass_of($enumClass, \BackedEnum::class) === false) {
            return Result::fail(new Reason('type.enum', 'Expected an enum value.'));
        }

        $value = $raw->value;

        // Массив/объект вместо скаляра — сломанный клиент: type.*, fallback такое не глотает.
        if (\is_string($value) === false && \is_int($value) === false) {
            return Result::fail(new Reason('type.enum', 'Expected a scalar enum value.'));
        }

        // Normalized — флаг здесь, а не декоратор: сопоставление требует $enum::cases(),
        // которых декоратор не видит. Осознанная асимметрия со схемой декораторов.
        if ($plan->normalized && \is_string($value)) {
            $needle = mb_strtolower(trim($value));
            foreach ($enumClass::cases() as $backedEnum) {
                if (mb_strtolower((string)$backedEnum->value) === $needle) {
                    return Result::ok($backedEnum);
                }
            }

            return Result::fail(new Reason('enum.unknown', 'Unknown value.'));
        }

        $backedEnum = \is_int($value)
            ? $enumClass::tryFrom($value)
            : $this->tryFromString($enumClass, $value);

        if ($backedEnum === null) {
            return Result::fail(new Reason('enum.unknown', 'Unknown value.'));
        }

        return Result::ok($backedEnum);
    }

    /**
     * @param class-string<\BackedEnum> $enumClass
     */
    private function tryFromString(string $enumClass, string $value): ?\BackedEnum
    {
        $backingType = (string)new \ReflectionEnum($enumClass)->getBackingType();

        if ($backingType === 'int') {
            $filtered = filter_var($value, \FILTER_VALIDATE_INT);

            return $filtered === false ? null : $enumClass::tryFrom($filtered);
        }

        return $enumClass::tryFrom($value);
    }
}
