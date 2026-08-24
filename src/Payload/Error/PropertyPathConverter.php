<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Error;

/**
 * Конвертация нотации симфонийского валидатора в указатели маппера:
 * 'items[0].quantity' → 'items/0/quantity'. Без неё указатели от кастеров
 * и от валидатора окажутся в разных нотациях и фронт не сможет их обрабатывать единообразно.
 */
final readonly class PropertyPathConverter
{
    public static function toPointer(string $propertyPath): string
    {
        return str_replace(['[', ']', '.'], ['/', '', '/'], $propertyPath);
    }
}
