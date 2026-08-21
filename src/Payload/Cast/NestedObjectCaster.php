<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast;

use Leads\Core\Payload\Error\Reason;
use Leads\Core\Payload\ObjectAssembler;
use Leads\Core\Payload\Plan\PlanFactory;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Payload\Result;

final readonly class NestedObjectCaster implements ValueCaster
{
    public function __construct(
        private PlanFactory $plans,
        private ObjectAssembler $assembler,
    ) {
    }

    public function supports(PropertyPlan $plan): bool
    {
        return $plan->nestedClass !== null;
    }

    public function cast(RawValue $raw, PropertyPlan $plan): Result
    {
        $value = $raw->value;

        if (\is_array($value) === false) {
            return Result::fail(new Reason('type.object', 'Expected an object.'));
        }

        // {} и [] неразличимы после json_decode — оба дают пустой массив. Отклонять
        // через array_is_list() нельзя: {"address": {}} должен дойти до сборки
        // и дать нормальные required-ошибки по полям, а не type.object.
        $nestedClass = $plan->nestedClass
            ?? throw new \LogicException(sprintf('Plan for property %s has no nested class.', $plan->name));

        return $this->assembler->assemble($this->plans->build($nestedClass, nested: true), $value, $raw->depth + 1);
    }
}
