<?php

declare(strict_types=1);

namespace Leads\Core\CommandBus;

class CommandValidatorException extends \Exception
{
    public function __construct(string $message, int $code = 400)
    {
        parent::__construct($message, $code);
    }
}
