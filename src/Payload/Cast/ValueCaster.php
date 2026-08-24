<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast;

use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Payload\Result;

/**
 * Кастер значения. supports() вызывается только при построении плана;
 * в рантайме работает готовая цепочка $plan->casterChain.
 *
 * Реализации в src автоматически получают DI-тег payload.caster (_instanceof в services.yaml);
 * декораторы из Cast/Decorator исключены из контейнера и строятся только PlanFactory.
 */
interface ValueCaster
{
    public function supports(PropertyPlan $plan): bool;

    /**
     * @return Result<mixed>
     */
    public function cast(RawValue $raw, PropertyPlan $plan): Result;
}
