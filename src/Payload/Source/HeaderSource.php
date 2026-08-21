<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Source;

use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RequestContext;

final readonly class HeaderSource implements ValueSource
{
    public static function key(): string
    {
        return 'header';
    }

    public function has(RequestContext $context, PropertyPlan $plan): bool
    {
        return $context->header($plan->key) !== null;
    }

    public function extract(RequestContext $context, PropertyPlan $plan): mixed
    {
        return $context->header($plan->key);
    }
}
