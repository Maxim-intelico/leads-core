<?php

declare(strict_types=1);

namespace Leads\Core\Validator;

use Symfony\Component\Validator\Constraints\GroupSequence;

interface ApiValidatorInterface
{
    /**
     * @param array<GroupSequence|string>|null $groups
     */
    public function validate(object $object, ?array $groups = null): void;
}
