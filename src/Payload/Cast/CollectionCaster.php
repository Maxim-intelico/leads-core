<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast;

use Leads\Core\Payload\Error\Reason;
use Leads\Core\Payload\ObjectAssembler;
use Leads\Core\Payload\Plan\PlanFactory;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Payload\Result;

final readonly class CollectionCaster implements ValueCaster
{
    public function __construct(
        private PlanFactory $plans,
        private ObjectAssembler $assembler,
    ) {
    }

    public function supports(PropertyPlan $plan): bool
    {
        return $plan->itemClass !== null;
    }

    public function cast(RawValue $raw, PropertyPlan $plan): Result
    {
        $value = $raw->value;

        if (\is_array($value) === false || array_is_list($value) === false) {
            return Result::fail(new Reason('type.collection', 'Expected a list.'));
        }

        // Жёсткий предел здесь, а не Assert\Count: Count сработал бы после конструирования —
        // сто тысяч элементов это сто тысяч объектов в памяти до первой проверки.
        if (\count($value) > $plan->maxItems) {
            return Result::fail(new Reason(
                'collection.too_many',
                sprintf('Too many items; at most %d allowed.', $plan->maxItems),
                ['max' => $plan->maxItems],
            ));
        }

        $itemClass = $plan->itemClass
            ?? throw new \LogicException(sprintf('Plan for property %s has no item class.', $plan->name));

        $childPlan = $this->plans->build($itemClass, nested: true);

        $items = [];
        $reasons = [];

        foreach ($value as $index => $item) {
            if (\is_array($item) === false) {
                $reasons[] = new Reason('type.object', 'Expected an object.')->nest($index);

                break;
            }

            $result = $this->assembler->assemble($childPlan, $item, $raw->depth + 1);

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
