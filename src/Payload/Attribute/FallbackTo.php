<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Attribute;

/**
 * Значение по умолчанию при НЕ-типовой ошибке каста (enum.unknown и т.п.).
 *
 * Для enum принимает кейс, а не строку: опечатка становится ошибкой компиляции.
 * Типовые ошибки (type.*) fallback не восстанавливает — сломанный клиент получает 400.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class FallbackTo
{
    public function __construct(
        public \BackedEnum|string|int|float|bool|null $value,
    ) {
    }
}
