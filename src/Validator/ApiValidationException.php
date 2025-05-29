<?php

declare(strict_types=1);

namespace Leads\Core\Validator;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class ApiValidationException extends \Exception
{
    /** @var array<array-key, array{property: string, message: string}> */
    public array $errors;

    public function __construct(ConstraintViolationListInterface $violationList)
    {
        $errors = [];

        foreach ($violationList as $violation) {
            $errors[] = [
                'property' => $violation->getPropertyPath(),
                'message' => (string)$violation->getMessage(),
            ];
        }

        $this->errors = $errors;

        parent::__construct((string)json_encode($errors));
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
