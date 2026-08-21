<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Error;

use Symfony\Component\HttpFoundation\Response;

/**
 * Граница 400/422 зафиксирована в ADR: 400 — не разобрали (кастеры),
 * 422 — разобрали, но правило не выполнено (валидатор).
 * Временно отключено: валидатор тоже отдаёт 400 (совместимость с потребителями).
 */
final class PayloadViolationException extends \RuntimeException implements HttpProblemException
{
    private function __construct(
        private readonly ViolationList $violations,
        private readonly int $statusCode,
    ) {
        parent::__construct('Request payload is invalid.');
    }

    public static function fromCast(ViolationList $violations): self
    {
        return new self($violations, Response::HTTP_BAD_REQUEST);
    }

    public static function fromValidator(ViolationList $violations): self
    {
        return new self($violations, Response::HTTP_BAD_REQUEST);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getProblemType(): string
    {
        return '/errors/validation';
    }

    public function getTitle(): string
    {
        return 'Request payload is invalid';
    }

    public function getViolations(): ViolationList
    {
        return $this->violations;
    }
}
