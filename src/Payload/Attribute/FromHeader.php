<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class FromHeader
{
    /**
     * Имя заголовка обязательно: '#[FromHeader] $xRequestSource' скрывал бы реальное имя.
     * Отсутствие имени — ошибка компиляции, а не построения плана.
     */
    public function __construct(
        public string $name,
    ) {
    }
}
