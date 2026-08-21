<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast;

use Leads\Core\Payload\Plan\PropertyPlan;

/**
 * Реестр кастеров. Хранит ленивый iterable (tagged_iterator) и НЕ материализует его
 * в конструкторе — это разрывает цикл PlanFactory → CasterRegistry → NestedObjectCaster →
 * PlanFactory при построении контейнера.
 */
final readonly class CasterRegistry
{
    /**
     * @param iterable<ValueCaster> $casters
     */
    public function __construct(
        private iterable $casters,
    ) {
    }

    public function resolve(PropertyPlan $plan): ?ValueCaster
    {
        foreach ($this->casters as $caster) {
            if ($caster->supports($plan)) {
                return $caster;
            }
        }

        return null;
    }
}
