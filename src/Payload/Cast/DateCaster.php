<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast;

use Leads\Core\Payload\Error\Reason;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Payload\Result;

/**
 * Даты и datetime в одном кастере: у обоих объявленный тип \DateTimeImmutable,
 * а supports() кастеров обязаны быть дизъюнктны — разделить их по типу свойства нельзя.
 *
 * '!Y-m-d' — «!» обнуляет время; без него подставится текущее, и fromDate
 * станет зависеть от момента запроса. По той же причине «!» стоит и у форматов
 * без секунд — иначе секунды возьмутся из момента запроса.
 */
final class DateCaster implements ValueCaster
{
    private const string DATE_FORMAT = '!Y-m-d';

    private const array DATE_TIME_FORMATS = [
        'Y-m-d H:i:s',
        \DateTimeInterface::ATOM,
        'Y-m-d\TH:i:s',
        '!Y-m-d\TH:i',
        '!Y-m-d H:i',
    ];

    public function supports(PropertyPlan $plan): bool
    {
        return $plan->type === \DateTimeImmutable::class;
    }

    public function cast(RawValue $raw, PropertyPlan $plan): Result
    {
        $value = $raw->value;

        if (\is_string($value) === false || $value === '') {
            return Result::fail(new Reason('type.date', 'Expected a date string.'));
        }

        $formats = preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
            ? [self::DATE_FORMAT]
            : self::DATE_TIME_FORMATS;

        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            $errors = \DateTimeImmutable::getLastErrors();

            // На '2026-02-31' createFromFormat не падает, а молча переносит на 3 марта —
            // это warning, и его обязательно проверять. getLastErrors(): array|false.
            if ($date !== false && (\is_array($errors) === false || $errors['warning_count'] === 0)) {
                return Result::ok($date);
            }
        }

        return Result::fail(
            new Reason('type.date', 'Expected a date in YYYY-MM-DD format or a datetime in ISO 8601 format.'),
        );
    }
}
