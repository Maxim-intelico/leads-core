<?php

declare(strict_types=1);

namespace Leads\Core\Exception;

class AccessDeniedException extends \RuntimeException
{
    public function __construct(
        string $message = 'Access Denied.',
        int $code = 403,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(message: $message, code: $code, previous: $previous);
    }
}
