<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Plan;

use Leads\Core\Payload\Attribute\Clamp;
use Leads\Core\Payload\Attribute\FallbackTo;
use Leads\Core\Payload\Attribute\FreeForm;
use Leads\Core\Payload\Attribute\FromBody;
use Leads\Core\Payload\Attribute\FromHeader;
use Leads\Core\Payload\Attribute\FromPath;
use Leads\Core\Payload\Attribute\FromQuery;
use Leads\Core\Payload\Attribute\HttpPayload;
use Leads\Core\Payload\Attribute\MaxItems;
use Leads\Core\Payload\Attribute\Normalized;
use Leads\Core\Payload\Attribute\Split;
use Leads\Core\Payload\Cast\CasterRegistry;
use Leads\Core\Payload\Cast\Decorator\ClampCaster;
use Leads\Core\Payload\Cast\Decorator\FallbackCaster;
use Leads\Core\Payload\Cast\Decorator\NullableCaster;
use Leads\Core\Payload\Cast\Decorator\SplitCaster;
use Leads\Core\Payload\Cast\Decorator\TrimCaster;
use Leads\Core\Payload\Cast\ValueCaster;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\TypeContext\TypeContextFactory;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\TypeInfo\TypeResolver\PhpDocAwareReflectionTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\StringTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * Строит план DTO один раз: рефлексия и чтение атрибутов не должны выполняться на каждый запрос.
 * Все ошибки конфигурации — \LogicException на построении, не в рантайме маппинга.
 */
final readonly class PlanFactory
{
    private PhpDocAwareReflectionTypeResolver $docBlockTypeResolver;

    public function __construct(
        private CasterRegistry $registry,
    ) {
        $stringTypeResolver = new StringTypeResolver();
        $this->docBlockTypeResolver = new PhpDocAwareReflectionTypeResolver(
            TypeResolver::create(),
            $stringTypeResolver,
            new TypeContextFactory($stringTypeResolver),
        );
    }

    /**
     * @param class-string $class
     */
    public function build(string $class, bool $nested = false): PayloadPlan
    {
        $reflection = new \ReflectionClass($class);
        $constructor = $reflection->getConstructor()
            ?? throw new \LogicException(sprintf('Request DTO %s must have a constructor.', $class));

        $httpPayloadAttributes = $reflection->getAttributes(HttpPayload::class);
        $httpPayload = $httpPayloadAttributes === []
            ? new HttpPayload()
            : $httpPayloadAttributes[0]->newInstance();

        $properties = [];
        $bodyKeys = [];
        $pointerMap = [];

        foreach ($constructor->getParameters() as $reflectionParameter) {
            $property = $this->buildProperty($reflection, $reflectionParameter, $nested);
            $properties[] = $property;
            $pointerMap[$property->name] = $property->pointer;

            if ($property->sourceKey === 'body') {
                $bodyKeys[] = $property->key;
            }
        }

        return new PayloadPlan(
            class: $class,
            properties: $properties,
            bodyKeys: $bodyKeys,
            pointerMap: $pointerMap,
            maxBodyBytes: $httpPayload->maxBodyBytes,
            groups: $httpPayload->groups,
        );
    }

    /**
     * @param \ReflectionClass<object> $reflection
     */
    private function buildProperty(
        \ReflectionClass $reflection,
        \ReflectionParameter $parameter,
        bool $nested,
    ): PropertyPlan {
        $class = $reflection->getName();
        $name = $parameter->getName();

        [$sourceKey, $key] = $this->resolveSource($class, $parameter, $nested);

        $type = $parameter->getType();
        if (($type instanceof \ReflectionNamedType) === false) {
            throw new \LogicException(sprintf(
                'Property %s::$%s must have a single named type; union/intersection/missing types are not supported.',
                $class,
                $name,
            ));
        }

        $typeName = $type->getName();
        $enumClass = is_subclass_of($typeName, \BackedEnum::class) ? $typeName : null;
        $nestedClass = $this->resolveNestedClass($typeName, $enumClass);
        $normalized = $parameter->getAttributes(Normalized::class) !== [];
        $freeForm = $parameter->getAttributes(FreeForm::class) !== [];
        [$itemClass, $itemPlan] = $typeName === 'array'
            ? $this->resolveCollectionItem($reflection, $name, $normalized)
            : [null, null];

        if ($freeForm && $typeName !== 'array') {
            throw new \LogicException(sprintf(
                'Property %s::$%s: #[FreeForm] requires an array-typed property.',
                $class,
                $name,
            ));
        }

        if ($freeForm && ($itemClass !== null || $itemPlan !== null)) {
            throw new \LogicException(sprintf(
                'Property %s::$%s combines #[FreeForm] with a declared item type; remove one of them.',
                $class,
                $name,
            ));
        }

        if ($typeName === 'array' && $freeForm === false && $itemClass === null && $itemPlan === null) {
            throw new \LogicException(sprintf(
                'Property %s::$%s is a plain array; declare the item type'
                . ' via "@param list<ItemClass>" or "@param list<string>" on the constructor docblock.',
                $class,
                $name,
            ));
        }

        // Дочерние объекты конструируем сами — без Assert\Valid валидатор внутрь не зайдёт,
        // и правила на вложенных полях молча не выполнятся. Каскад требуем только когда
        // в графе дочернего класса правила есть: read-фильтрам без констрейнтов Valid не нужен.
        $childClass = $nestedClass ?? $itemClass;

        if (
            $childClass !== null
            && $this->hasValidConstraint($reflection, $name) === false
            && $this->hasValidationRules($childClass)
        ) {
            throw new \LogicException(sprintf(
                'Nested property %s::$%s requires #[Assert\Valid]:'
                . ' %s declares validation constraints and they would be silently skipped.',
                $class,
                $name,
                $childClass,
            ));
        }

        $fallbackAttributes = $parameter->getAttributes(FallbackTo::class);
        $clampAttributes = $parameter->getAttributes(Clamp::class);
        $splitAttributes = $parameter->getAttributes(Split::class);

        // Одно правило закрывает Split на скаляре, на объектной коллекции и на FreeForm.
        if ($splitAttributes !== [] && $itemPlan === null) {
            throw new \LogicException(sprintf(
                'Property %s::$%s: #[Split] requires a scalar collection ("@param list<string>" docblock).',
                $class,
                $name,
            ));
        }

        $maxItemsAttributes = $parameter->getAttributes(MaxItems::class);
        $maxItems = $maxItemsAttributes === [] ? null : $maxItemsAttributes[0]->newInstance()->max;

        // FreeForm попадает под это же правило: freeForm ⇒ itemClass и itemPlan null,
        // а лимита элементов у произвольной структуры нет по дизайну.
        if ($maxItems !== null && $itemClass === null && $itemPlan === null) {
            throw new \LogicException(sprintf(
                'Property %s::$%s: #[MaxItems] requires a collection property.',
                $class,
                $name,
            ));
        }

        if ($maxItems !== null && $maxItems < 1) {
            throw new \LogicException(sprintf(
                'Property %s::$%s: #[MaxItems] must be a positive item limit, %d given.',
                $class,
                $name,
                $maxItems,
            ));
        }

        $plan = new PropertyPlan(
            name: $name,
            key: $key,
            sourceKey: $sourceKey,
            pointer: $nested ? $key : $sourceKey . '/' . $key,
            type: $typeName,
            nullable: $type->allowsNull(),
            hasDefault: $parameter->isDefaultValueAvailable(),
            isStringLike: $typeName === 'string',
            enumClass: $enumClass,
            nestedClass: $nestedClass,
            itemClass: $itemClass,
            itemPlan: $itemPlan,
            maxItems: $maxItems ?? PropertyPlan::DEFAULT_MAX_ITEMS,
            clamp: $clampAttributes === [] ? null : $clampAttributes[0]->newInstance(),
            split: $splitAttributes === [] ? null : $splitAttributes[0]->newInstance(),
            freeForm: $freeForm,
            fallback: $fallbackAttributes === [] ? null : $fallbackAttributes[0]->newInstance()->value,
            hasFallback: $fallbackAttributes !== [],
            normalized: $normalized,
        );

        return $plan->withCasterChain($this->buildChain($class, $plan));
    }

    /**
     * @return array{string, string} [sourceKey, key]
     */
    private function resolveSource(string $class, \ReflectionParameter $parameter, bool $nested): array
    {
        $name = $parameter->getName();
        $sources = [];

        foreach ($parameter->getAttributes(FromBody::class) as $attribute) {
            $sources[] = ['body', $attribute->newInstance()->key ?? $name];
        }
        foreach ($parameter->getAttributes(FromQuery::class) as $attribute) {
            $sources[] = ['query', $attribute->newInstance()->key ?? $name];
        }
        foreach ($parameter->getAttributes(FromPath::class) as $attribute) {
            $sources[] = ['path', $attribute->newInstance()->key ?? $name];
        }
        foreach ($parameter->getAttributes(FromHeader::class) as $attribute) {
            $sources[] = ['header', $attribute->newInstance()->name];
        }

        if ($nested) {
            if ($sources !== []) {
                // Значение вложенного свойства приходит из массива родителя; молча
                // игнорировать атрибут нельзя — он выглядел бы работающим.
                throw new \LogicException(sprintf(
                    'Source attribute on nested property %s::$%s is meaningless;'
                    . ' the value comes from the parent array.',
                    $class,
                    $name,
                ));
            }

            return ['', $name];
        }

        if ($sources === []) {
            throw new \LogicException(sprintf(
                'Property %s::$%s has no source attribute;'
                . ' declare #[FromBody], #[FromQuery], #[FromPath] or #[FromHeader].',
                $class,
                $name,
            ));
        }

        if (\count($sources) > 1) {
            throw new \LogicException(sprintf(
                'Property %s::$%s declares more than one source attribute.',
                $class,
                $name,
            ));
        }

        return $sources[0];
    }

    /**
     * @return ?class-string
     */
    private function resolveNestedClass(string $typeName, ?string $enumClass): ?string
    {
        if ($enumClass !== null || class_exists($typeName) === false) {
            return null;
        }

        // Дата — это скаляр публичного контракта со своим кастером, а не вложенная структура.
        if (is_a($typeName, \DateTimeInterface::class, true)) {
            return null;
        }

        return $typeName;
    }

    /**
     * @param \ReflectionClass<object> $reflection
     *
     * @return array{?class-string, ?PropertyPlan} [itemClass, itemPlan] — ровно один не null,
     *                                             либо оба null (тип элемента не объявлен)
     */
    private function resolveCollectionItem(
        \ReflectionClass $reflection,
        string $propertyName,
        bool $normalized,
    ): array {
        $valueType = $this->resolveCollectionValueType($reflection, $propertyName);
        $class = $reflection->getName();

        // mixed приравнен к «тип не объявлен»: плоский array без докблока
        // резолвится в array<array-key, mixed> — отличить его от list<mixed> нельзя.
        $isMixed = $valueType instanceof BuiltinType && $valueType->getTypeIdentifier() === TypeIdentifier::MIXED;

        if ($valueType === null || $isMixed) {
            return [null, null];
        }

        if ($valueType instanceof NullableType) {
            throw new \LogicException(sprintf(
                'Nullable collection items on %s::$%s are not supported;'
                . ' declare "@var list<string>", not "@var list<?string>".',
                $class,
                $propertyName,
            ));
        }

        if ($valueType instanceof ObjectType) {
            $itemClassName = $valueType->getClassName();

            if (is_subclass_of($itemClassName, \BackedEnum::class)) {
                return [
                    null,
                    $this->buildScalarItemPlan($class, $propertyName, $itemClassName, $itemClassName, $normalized),
                ];
            }

            // Та же граница, что в resolveNestedClass: дата — скаляр публичного контракта.
            if (is_a($itemClassName, \DateTimeInterface::class, true)) {
                return [
                    null,
                    $this->buildScalarItemPlan($class, $propertyName, \DateTimeImmutable::class, null, $normalized),
                ];
            }

            return [$itemClassName, null];
        }

        if ($valueType instanceof BuiltinType) {
            $identifier = $valueType->getTypeIdentifier();
            $scalars = [TypeIdentifier::STRING, TypeIdentifier::INT, TypeIdentifier::FLOAT, TypeIdentifier::BOOL];

            if (\in_array($identifier, $scalars, true)) {
                return [null, $this->buildScalarItemPlan($class, $propertyName, $identifier->value, null, $normalized)];
            }
        }

        throw new \LogicException(sprintf(
            'Unsupported collection item type "%s" for %s::$%s.',
            (string)$valueType,
            $class,
            $propertyName,
        ));
    }

    /**
     * @param \ReflectionClass<object> $reflection
     */
    private function resolveCollectionValueType(\ReflectionClass $reflection, string $propertyName): ?Type
    {
        $type = $this->docBlockTypeResolver->resolve($reflection->getProperty($propertyName));

        if ($type instanceof NullableType) {
            $type = $type->getWrappedType();
        }

        if (($type instanceof CollectionType) === false) {
            return null;
        }

        return $type->getCollectionValueType();
    }

    /**
     * План скалярного элемента компилируется на build вместе с цепочкой —
     * рантайм только гоняет её по элементам. Nullable/Clamp/Fallback на элементы
     * сознательно не распространяются: это атрибуты уровня свойства.
     *
     * @param ?class-string<\BackedEnum> $enumClass
     */
    private function buildScalarItemPlan(
        string $class,
        string $propertyName,
        string $itemType,
        ?string $enumClass,
        bool $normalized,
    ): PropertyPlan {
        $plan = new PropertyPlan(
            name: $propertyName . '[]',
            key: '',
            sourceKey: '',
            pointer: '',
            type: $itemType,
            nullable: false,
            hasDefault: false,
            isStringLike: $itemType === 'string',
            enumClass: $enumClass,
            normalized: $normalized,
        );

        return $plan->withCasterChain($this->buildChain($class, $plan));
    }

    /**
     * @param \ReflectionClass<object> $reflection
     */
    private function hasValidConstraint(\ReflectionClass $reflection, string $propertyName): bool
    {
        return $reflection->getProperty($propertyName)->getAttributes(Valid::class) !== [];
    }

    /**
     * Есть ли в графе классов хоть одно валидационное правило: атрибуты-констрейнты
     * на классе, публичных методах (Assert\Callback) или параметрах конструктора,
     * рекурсивно по вложенным объектам и коллекциям.
     *
     * @param class-string $class
     * @param array<class-string, true> $visited
     */
    private function hasValidationRules(string $class, array &$visited = []): bool
    {
        if (isset($visited[$class])) {
            return false;
        }

        $visited[$class] = true;
        $reflection = new \ReflectionClass($class);

        if ($reflection->getAttributes(Constraint::class, \ReflectionAttribute::IS_INSTANCEOF) !== []) {
            return true;
        }

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            if ($reflectionMethod->getAttributes(Constraint::class, \ReflectionAttribute::IS_INSTANCEOF) !== []) {
                return true;
            }
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return false;
        }

        foreach ($constructor->getParameters() as $reflectionParameter) {
            $constraints = $reflectionParameter->getAttributes(Constraint::class, \ReflectionAttribute::IS_INSTANCEOF);

            foreach ($constraints as $attribute) {
                // Сам Valid правил не добавляет — правила ищет рекурсия ниже.
                if (is_a($attribute->getName(), Valid::class, true) === false) {
                    return true;
                }
            }

            $childClass = $this->childClassOf($reflection, $reflectionParameter);

            if ($childClass !== null && $this->hasValidationRules($childClass, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Класс дочернего объекта параметра: вложенный DTO либо item-класс коллекции.
     * Скаляры контракта (enum, дата) — не дочерние структуры, как в resolveNestedClass.
     *
     * @param \ReflectionClass<object> $reflection
     *
     * @return ?class-string
     */
    private function childClassOf(\ReflectionClass $reflection, \ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (($type instanceof \ReflectionNamedType) === false) {
            return null;
        }

        $typeName = $type->getName();

        if ($typeName === 'array') {
            $valueType = $this->resolveCollectionValueType($reflection, $parameter->getName());

            if (($valueType instanceof ObjectType) === false) {
                return null;
            }

            $typeName = $valueType->getClassName();
        }

        if (class_exists($typeName) === false || is_subclass_of($typeName, \BackedEnum::class)) {
            return null;
        }

        if (is_a($typeName, \DateTimeInterface::class, true)) {
            return null;
        }

        return $typeName;
    }

    private function buildChain(string $class, PropertyPlan $plan): ValueCaster
    {
        $caster = $this->registry->resolve($plan)
            ?? throw new \LogicException(sprintf(
                'No caster supports type "%s" (property %s::$%s).',
                $plan->type,
                $class,
                $plan->name,
            ));

        // Порядок обёрток не произволен: Split самый внутренний (строка должна стать списком
        // до всех проверок), Trim снаружи Nullable (иначе '?email=%20%20' не станет null),
        // Fallback самый внешний — ловит провалы всей цепочки.
        if ($plan->split !== null) {
            $separator = $plan->split->separator;

            if ($separator === '') {
                throw new \LogicException(sprintf(
                    'Property %s::$%s declares #[Split] with an empty separator.',
                    $class,
                    $plan->name,
                ));
            }

            $caster = new SplitCaster($caster, $separator);
        }

        if ($plan->clamp !== null) {
            $caster = new ClampCaster($caster, $plan->clamp->min, $plan->clamp->max);
        }

        if ($plan->nullable) {
            $caster = new NullableCaster($caster);
        }

        if ($plan->isStringLike) {
            $caster = new TrimCaster($caster);
        }

        if ($plan->hasFallback) {
            $caster = new FallbackCaster($caster, $plan->fallback);
        }

        return $caster;
    }
}
