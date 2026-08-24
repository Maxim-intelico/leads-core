<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast\Decorator;

use Leads\Core\Payload\Cast\ValueCaster;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Payload\Result;

/**
 * Строка с разделителем → список до коллекционного кастера ('?ids=a,b,c').
 *
 * Уже-список проходит без изменений — синтаксис '?ids[]=a&ids[]=b' продолжает работать.
 * Поэлементная обрезка не здесь: TrimCaster цепочки элемента режет 'a, b , c' сам.
 * Лимит maxItems после разбиения проверяет базовый кастер.
 */
final readonly class SplitCaster implements ValueCaster
{
    /**
     * @param non-empty-string $separator
     */
    public function __construct(
        private ValueCaster $inner,
        private string $separator,
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

        // '?ids=' — это «прислали пустой список», а не список из одной пустой строки.
        $items = $value === '' ? [] : explode($this->separator, $value);

        return $this->inner->cast(RawValue::of($items, $raw->depth), $plan);
    }
}
