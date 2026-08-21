<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Error;

use Symfony\Component\HttpFoundation\Response;

final class MalformedPayloadException extends \RuntimeException implements HttpProblemException
{
    public function __construct(
        private readonly string $detail = 'Request body is not valid JSON.',
        private readonly string $errorCode = 'json.malformed',
    ) {
        parent::__construct($this->detail);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getProblemType(): string
    {
        return '/errors/malformed-payload';
    }

    public function getTitle(): string
    {
        return 'Request payload is malformed';
    }

    public function getViolations(): ViolationList
    {
        return new ViolationList([new Violation('body', $this->detail, $this->errorCode)]);
    }
}
