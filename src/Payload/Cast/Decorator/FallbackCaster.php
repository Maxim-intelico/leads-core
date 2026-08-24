<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Cast\Decorator;

use Leads\Core\Payload\Cast\ValueCaster;
use Leads\Core\Payload\Error\Reason;
use Leads\Core\Payload\Plan\PropertyPlan;
use Leads\Core\Payload\RawValue;
use Leads\Core\Payload\Result;

/**
 * Восстанавливает только НЕ-типовые ошибки: '?sortBy=password' — неаккуратный клиент,
 * молча даём дефолт; '?sortBy[]=a' (массив вместо строки) — сломанный клиент, отдаём 400.
 */
final readonly class FallbackCaster implements ValueCaster
{
    public function __construct(
        private ValueCaster $inner,
        private mixed $fallback,
    ) {
    }

    public function supports(PropertyPlan $plan): bool
    {
        return $this->inner->supports($plan);
    }

    public function cast(RawValue $raw, PropertyPlan $plan): Result
    {
        $result = $this->inner->cast($raw, $plan);

        if ($result->isOk()) {
            return $result;
        }

        if (array_any($result->reasons(), static fn (Reason $r): bool => str_starts_with($r->code, 'type.'))) {
            return $result;
        }

        return Result::ok($this->fallback);
    }
}
