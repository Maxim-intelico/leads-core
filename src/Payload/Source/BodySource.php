<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Source;

use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RequestContext;

final readonly class BodySource implements ValueSource
{
    public static function key(): string
    {
        return 'body';
    }

    public function has(RequestContext $context, PropertyPlan $plan): bool
    {
        return \array_key_exists($plan->key, $context->body());
    }

    public function extract(RequestContext $context, PropertyPlan $plan): mixed
    {
        return $context->body()[$plan->key] ?? null;
    }
}
