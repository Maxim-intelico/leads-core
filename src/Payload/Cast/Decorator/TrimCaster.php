<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast\Decorator;

use Leads\Core\Payload\Cast\ValueCaster;
use Leads\Core\Payload\Error\Reason;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Payload\Result;

/**
 * Безусловная обрезка строковых свойств — триггер это объявленный тип string, атрибута нет.
 * VO-свойства сюда не попадают: у них свой кастер, и обрезка там была бы ошибкой.
 *
 * Стандартного trim() недостаточно: его charlist не включает неразрывный пробел U+00A0
 * и BOM U+FEFF — именно они приезжают при копировании из Word/Excel/веб-страницы.
 * mb_trim() (PHP 8.4) режет unicode-пробелы включая U+00A0; BOM формально не пробел
 * (категория Cf), поэтому дописан в charlist явно.
 */
final readonly class TrimCaster implements ValueCaster
{
    private const string CHARACTERS = " \t\n\r\0\x0B\u{00A0}\u{FEFF}";

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

        if (\is_string($value) === false) {
            return $this->inner->cast($raw, $plan);
        }

        // Битый UTF-8 не должен доехать до БД; mb_trim() невалидную кодировку не отлавливает.
        if (mb_check_encoding($value, 'UTF-8') === false) {
            return Result::fail(new Reason('type.encoding', 'Expected valid UTF-8.'));
        }

        return $this->inner->cast(RawValue::of(mb_trim($value, self::CHARACTERS), $raw->depth), $plan);
    }
}
