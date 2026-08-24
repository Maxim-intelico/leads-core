<?php

declare(strict_types=1);

namespace Leads\Core\Payload\Error;

/**
 * @implements \IteratorAggregate<int, Violation>
 */
final readonly class ViolationList implements \Countable, \IteratorAggregate, \JsonSerializable
{
    /**
     * @param list<Violation> $violations
     */
    public function __construct(
        private array $violations,
    ) {
    }

    public static function fromReasons(Reason ...$reasons): self
    {
        $violations = [];
        foreach ($reasons as $reason) {
            $violations[] = Violation::fromReason($reason);
        }

        return new self($violations);
    }

    /**
     * @return list<Violation>
     */
    public function all(): array
    {
        return $this->violations;
    }

    public function count(): int
    {
        return \count($this->violations);
    }

    /**
     * @return \ArrayIterator<int<0, max>, Violation>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->violations);
    }

    /**
     * @return list<array{pointer: string, detail: string, code: string}>
     */
    public function jsonSerialize(): array
    {
        $serialized = [];
        foreach ($this->violations as $violation) {
            $serialized[] = $violation->jsonSerialize();
        }

        return $serialized;
    }
}
