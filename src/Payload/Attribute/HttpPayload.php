<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Attribute;

/**
 * Настройки DTO целиком. Отсутствие атрибута эквивалентно дефолтам.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class HttpPayload
{
    public const int DEFAULT_MAX_BODY_BYTES = 1_048_576;

    /**
     * @param ?list<string> $groups группы валидации; null — дефолтные
     */
    public function __construct(
        public ?array $groups = null,
        public int $maxBodyBytes = self::DEFAULT_MAX_BODY_BYTES,
    ) {
    }
}
