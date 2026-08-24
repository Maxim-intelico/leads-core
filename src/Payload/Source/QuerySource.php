<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Source;

use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RequestContext;

final readonly class QuerySource implements ValueSource
{
    public static function key(): string
    {
        return 'query';
    }

    public function has(RequestContext $context, PropertyPlan $plan): bool
    {
        return \array_key_exists($plan->key, $context->query());
    }

    public function extract(RequestContext $context, PropertyPlan $plan): mixed
    {
        return $context->query()[$plan->key] ?? null;
    }
}
