<?php

declare(strict_types=1);

namespace Leads\Core\Exception;

class EntityNotFoundException extends \LogicException
{
    public function __construct()
    {
        parent::__construct('Entity not found', 404);
    }
}
