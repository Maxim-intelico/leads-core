<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Attribute;

/**
 * Произвольная JSON-структура без схемы: значение проходит в DTO как есть.
 *
 * Явный опт-ин, а не докблок: TypeInfo не отличает отсутствие докблока
 * от "array<string, mixed>" — оба резолвятся в mixed. Защита от DoS —
 * байтовый и глубинный лимиты RequestContext; констрейнты валидатора
 * на свойстве работают как обычно.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class FreeForm
{
}
