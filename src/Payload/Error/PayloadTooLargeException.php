<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Error;

use Symfony\Component\HttpFoundation\Response;

final class PayloadTooLargeException extends \RuntimeException implements HttpProblemException
{
    public function __construct(
        private readonly int $maxBodyBytes,
    ) {
        parent::__construct(sprintf('Request body exceeds the limit of %d bytes.', $this->maxBodyBytes));
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_REQUEST_ENTITY_TOO_LARGE;
    }

    public function getProblemType(): string
    {
        return '/errors/payload-too-large';
    }

    public function getTitle(): string
    {
        return 'Request payload is too large';
    }

    public function getViolations(): ViolationList
    {
        return new ViolationList([
            new Violation(
                'body',
                sprintf('Request body exceeds the limit of %d bytes.', $this->maxBodyBytes),
                'payload.too_large',
            ),
        ]);
    }
}
