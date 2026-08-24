<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Attribute;

/**
 * Регистронезависимое сопоставление значения с кейсами enum.
 *
 * Единственная политика, реализованная флагом внутри кастера (EnumCaster), а не декоратором:
 * сопоставление требует доступа к $enum::cases(), которых декоратор не видит.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final readonly class Normalized
{
}
