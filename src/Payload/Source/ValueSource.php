<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Source;

use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RequestContext;

/**
 * Источник значений. Реализации в src автоматически получают DI-тег payload.source
 * (_instanceof в services.yaml) и индексируются по key() в tagged_iterator.
 *
 * has() отделён от extract(): «параметр не передан» и null — разные вещи,
 * иначе PATCH не реализуем («не менять» и «обнулить» неразличимы).
 */
interface ValueSource
{
    public static function key(): string;

    public function has(RequestContext $context, PropertyPlan $plan): bool;

    public function extract(RequestContext $context, PropertyPlan $plan): mixed;
}
