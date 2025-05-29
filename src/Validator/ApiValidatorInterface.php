<?php

declare(strict_types=1);

namespace Leads\Core\Validator;

interface ApiValidatorInterface
{
    public function validate(object $object, ?array $groups = null): void;
}
