<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Error;

/**
 * Ошибка с АБСОЛЮТНЫМ указателем ('body/items/0/quantity') — единица массива errors в ответе RFC 9457.
 */
final readonly class Violation implements \JsonSerializable
{
    public function __construct(
        public string $pointer,
        public string $detail,
        public string $code,
    ) {
    }

    /**
     * К этому моменту путь Reason уже абсолютный: префикс источника — это pointer корневого
     * свойства ('query/limit'), ассемблер докладывает его через nest(), спецкода не нужно.
     */
    public static function fromReason(Reason $reason): self
    {
        return new self($reason->path, $reason->detail, $reason->code);
    }

    /**
     * @return array{pointer: string, detail: string, code: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'pointer' => $this->pointer,
            'detail' => $this->detail,
            'code' => $this->code,
        ];
    }
}
