<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Plan;

use Leads\Core\Payload\Attribute\Clamp;
use Leads\Core\Payload\Attribute\Split;
use Leads\Core\Payload\Cast\ValueCaster;

/**
 * Скомпилированный план одного свойства DTO: всё, что нужно в рантайме,
 * чтобы не читать атрибуты и рефлексию на каждый запрос.
 */
final readonly class PropertyPlan
{
    public const int DEFAULT_MAX_ITEMS = 1000;

    /**
     * @param string $name имя параметра конструктора
     * @param string $key ключ в источнике (по умолчанию совпадает с name)
     * @param string $sourceKey 'body'|'query'|'path'|'header'
     * @param string $pointer абсолютный для корня ('query/limit'), относительный для вложенных ('quantity')
     * @param string $type имя встроенного типа ('int', 'string', ...) либо class-string
     * @param ?class-string<\BackedEnum> $enumClass
     * @param ?class-string $nestedClass
     * @param ?class-string $itemClass элементы-объекты; взаимоисключим с itemPlan —
     *                                 это гарантирует дизъюнктность supports() коллекционных кастеров
     * @param ?PropertyPlan $itemPlan скомпилированный план скалярного элемента коллекции
     * @param mixed $fallback различаем «нет fallback» и FallbackTo(null) парой fallback + hasFallback
     * @param bool $freeForm произвольная структура как есть; freeForm ⇒ itemPlan и itemClass null —
     *                       дизъюнктность с коллекционными кастерами гарантирует фабрика на build
     */
    public function __construct(
        public string $name,
        public string $key,
        public string $sourceKey,
        public string $pointer,
        public string $type,
        public bool $nullable,
        public bool $hasDefault,
        public bool $isStringLike,
        public ?string $enumClass = null,
        public ?string $nestedClass = null,
        public ?string $itemClass = null,
        public ?self $itemPlan = null,
        public int $maxItems = self::DEFAULT_MAX_ITEMS,
        public ?Clamp $clamp = null,
        public ?Split $split = null,
        public bool $freeForm = false,
        public mixed $fallback = null,
        public bool $hasFallback = false,
        public bool $normalized = false,
        public ?ValueCaster $casterChain = null,
    ) {
    }

    /**
     * Цепочка навешивается после разрешения кастера по уже собранному плану —
     * поэтому отдельный шаг, а не аргумент конструктора.
     */
    public function withCasterChain(ValueCaster $chain): self
    {
        return new self(
            name: $this->name,
            key: $this->key,
            sourceKey: $this->sourceKey,
            pointer: $this->pointer,
            type: $this->type,
            nullable: $this->nullable,
            hasDefault: $this->hasDefault,
            isStringLike: $this->isStringLike,
            enumClass: $this->enumClass,
            nestedClass: $this->nestedClass,
            itemClass: $this->itemClass,
            itemPlan: $this->itemPlan,
            maxItems: $this->maxItems,
            clamp: $this->clamp,
            split: $this->split,
            freeForm: $this->freeForm,
            fallback: $this->fallback,
            hasFallback: $this->hasFallback,
            normalized: $this->normalized,
            casterChain: $chain,
        );
    }
}
