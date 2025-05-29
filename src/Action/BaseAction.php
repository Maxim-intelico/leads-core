<?php

declare(strict_types=1);

namespace Leads\Core\Action;

use Leads\Core\Validator\ApiValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class BaseAction
{
    public function __construct(
        private ApiValidatorInterface $apiValidator,
    ) {
    }

    protected function validate(object $object, ?array $groups = null): void
    {
        $this->apiValidator->validate($object, $groups);
    }

    public function create200Response(array $data): JsonResponse
    {
        return new JsonResponse($data, Response::HTTP_OK);
    }

    public function create201EmptyResponse(): JsonResponse
    {
        return new JsonResponse([], Response::HTTP_CREATED);
    }

    public function create201Response(string $id): JsonResponse
    {
        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    public function create201ContentResponse(array $response): JsonResponse
    {
        return new JsonResponse($response, Response::HTTP_CREATED);
    }

    public function create204Response(): JsonResponse
    {
        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    public function create400Response(string $message): JsonResponse
    {
        return new JsonResponse($message, Response::HTTP_BAD_REQUEST);
    }

    public function createCustomResponse(array $data, int $status): JsonResponse
    {
        return new JsonResponse($data, $status);
    }
}
