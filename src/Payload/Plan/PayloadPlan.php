<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Plan;

/**
 * Скомпилированный план DTO целиком.
 */
final readonly class PayloadPlan
{
    /**
     * @param class-string $class
     * @param list<PropertyPlan> $properties
     * @param list<string> $bodyKeys ключи тела, известные плану — для учёта неизвестных полей
     * @param array<string, string> $pointerMap имя свойства → указатель ('toDate' → 'query/toDate')
     * @param ?list<string> $groups
     */
    public function __construct(
        public string $class,
        public array $properties,
        public array $bodyKeys,
        public array $pointerMap,
        public int $maxBodyBytes,
        public ?array $groups,
    ) {
    }
}
