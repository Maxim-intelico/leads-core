<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Attribute;

/**
 * Помечает аргумент контроллера для маппинга через PayloadMapper.
 *
 * Имя намеренно не MapPayload — чтобы не путалось с симфонийским MapRequestPayload.
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final readonly class Payload
{
    /**
     * @param ?list<string> $groups группы валидации; null — дефолтные
     */
    public function __construct(
        public ?array $groups = null,
    ) {
    }
}
