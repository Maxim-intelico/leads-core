<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast;

use Leads\Core\Payload\Error\Reason;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Payload\Result;

/**
 * Коллекции скалярных элементов (list<string> и т.п.). Дизъюнктность с CollectionCaster
 * гарантирована взаимоисключением itemClass/itemPlan в плане.
 */
final class ScalarCollectionCaster implements ValueCaster
{
    public function supports(PropertyPlan $plan): bool
    {
        return $plan->itemPlan !== null;
    }

    public function cast(RawValue $raw, PropertyPlan $plan): Result
    {
        $value = $raw->value;

        if (\is_array($value) === false || array_is_list($value) === false) {
            return Result::fail(new Reason('type.collection', 'Expected a list.'));
        }

        // Жёсткий предел здесь, а не Assert\Count: Count сработал бы после каста всех элементов.
        if (\count($value) > $plan->maxItems) {
            return Result::fail(new Reason(
                'collection.too_many',
                sprintf('Too many items; at most %d allowed.', $plan->maxItems),
                ['max' => $plan->maxItems],
            ));
        }

        $itemPlan = $plan->itemPlan
            ?? throw new \LogicException(sprintf('Plan for property %s has no item plan.', $plan->name));
        $chain = $itemPlan->casterChain
            ?? throw new \LogicException(sprintf('Item plan for property %s has no caster chain.', $plan->name));

        $items = [];
        $reasons = [];

        foreach ($value as $index => $item) {
            $result = $chain->cast(RawValue::of($item, $raw->depth + 1), $itemPlan);

            if ($result->isOk()) {
                $items[] = $result->value();

                continue;
            }

            foreach ($result->reasons() as $reason) {
                $reasons[] = $reason->nest($index);
            }

            break;   // fail-fast, как и в ассемблере
        }

        if ($reasons !== []) {
            return Result::fail(...$reasons);
        }

        return Result::ok($items);
    }
}
