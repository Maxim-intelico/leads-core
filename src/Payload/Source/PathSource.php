<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Source;

use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RequestContext;

final readonly class PathSource implements ValueSource
{
    public static function key(): string
    {
        return 'path';
    }

    public function has(RequestContext $context, PropertyPlan $plan): bool
    {
        return \array_key_exists($plan->key, $context->pathParams());
    }

    public function extract(RequestContext $context, PropertyPlan $plan): mixed
    {
        return $context->pathParams()[$plan->key] ?? null;
    }
}
