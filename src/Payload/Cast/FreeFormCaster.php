<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast;

use Leads\Core\Payload\Error\Reason;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Payload\Result;

/**
 * Произвольная JSON-структура как есть (#[FreeForm]). Проверок формы и maxItems
 * сознательно нет: от DoS защищают байтовый и глубинный лимиты RequestContext,
 * а констрейнты валидатора на свойстве работают после сборки как обычно.
 * Дизъюнктность с коллекционными кастерами гарантирует фабрика:
 * freeForm ⇒ itemPlan и itemClass null.
 */
final class FreeFormCaster implements ValueCaster
{
    public function supports(PropertyPlan $plan): bool
    {
        return $plan->freeForm;
    }

    public function cast(RawValue $raw, PropertyPlan $plan): Result
    {
        $value = $raw->value;

        if (\is_array($value) === false) {
            return Result::fail(new Reason('type.array', 'Expected an array.'));
        }

        return Result::ok($value);
    }
}
