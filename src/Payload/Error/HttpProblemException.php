<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Error;

/**
 * Контракт для PayloadExceptionListener: любое исключение с этим интерфейсом
 * превращается в ответ application/problem+json (RFC 9457).
 */
interface HttpProblemException extends \Throwable
{
    public function getStatusCode(): int;

    public function getProblemType(): string;

    public function getTitle(): string;

    public function getViolations(): ViolationList;
}
