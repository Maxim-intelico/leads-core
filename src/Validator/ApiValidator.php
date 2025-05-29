<?php

declare(strict_types=1);

namespace Leads\Core\Validator;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiValidator implements ApiValidatorInterface
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    public function validate(object $object, ?array $groups = null): void
    {
        $violations = $this->validator->validate($object, null, $groups);

        if ($violations->count() > 0) {
            throw new ApiValidationException($violations);
        }
    }
}
