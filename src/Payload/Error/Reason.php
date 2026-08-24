<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Error;

/**
 * Причина провала каста с ОТНОСИТЕЛЬНЫМ путём ('items/0/quantity').
 *
 * Кастер не знает своего абсолютного указателя; префикс источника ('body/', 'query/')
 * приклеивается выше — при конвертации в Violation.
 */
final readonly class Reason
{
    /**
     * @param array<string, scalar> $params только примитивы из публичного контракта — никаких сырых значений VO
     */
    public function __construct(
        public string $code,
        public string $detail,
        public array $params = [],
        public string $path = '',
    ) {
    }

    public function nest(string|int $segment): self
    {
        $path = $this->path === '' ? (string)$segment : $segment . '/' . $this->path;

        return new self($this->code, $this->detail, $this->params, $path);
    }
}
