<?php

declare(strict_types=1);

namespace Leads\Core\Payload;

use Leads\Core\Payload\Error\MalformedPayloadException;
use Leads\Core\Payload\Error\PayloadTooLargeException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Доступ к запросу для источников значений. Тело декодируется лениво, стадии строго по порядку:
 * лимит размера по фактически прочитанному (до json_decode) → json_validate (структура и глубина,
 * без построения массива) → json_decode.
 */
final class RequestContext
{
    private const int MAX_JSON_DEPTH = 32;

    /** @var array<array-key, mixed>|null */
    private ?array $body = null;

    private function __construct(
        private readonly Request $request,
        private readonly int $maxBodyBytes,
    ) {
    }

    public static function fromRequest(Request $request, int $maxBodyBytes): self
    {
        return new self($request, $maxBodyBytes);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function body(): array
    {
        if ($this->body !== null) {
            return $this->body;
        }

        $content = $this->request->getContent();

        if (\strlen($content) > $this->maxBodyBytes) {
            throw new PayloadTooLargeException($this->maxBodyBytes);
        }

        if ($content === '') {
            return $this->body = [];
        }

        if (json_validate($content, self::MAX_JSON_DEPTH) === false) {
            throw new MalformedPayloadException();
        }

        $decoded = json_decode($content, true);

        if (\is_array($decoded) === false) {
            throw new MalformedPayloadException('Request body must be a JSON object.', 'json.not_object');
        }

        return $this->body = $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return $this->request->query->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function pathParams(): array
    {
        /** @var array<string, mixed> */
        return (array)$this->request->attributes->get('_route_params', []);
    }

    public function header(string $name): ?string
    {
        return $this->request->headers->get($name);
    }

    public function routeName(): string
    {
        $route = $this->request->attributes->get('_route', '');

        return \is_string($route) ? $route : '';
    }
}
