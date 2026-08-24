<?php

declare(strict_types=1);

namespace Leads\Core\Payload;

use Leads\Core\Payload\Error\Reason;

/**
 * Результат каста: либо значение, либо список причин провала.
 *
 * Намеренно не монада: никаких map/flatMap/andThen — явный `if ($result->isOk())` читается лучше.
 *
 * @template-covariant T
 */
final readonly class Result
{
    /**
     * @param T|null $value
     * @param list<Reason> $reasons
     */
    private function __construct(
        private bool $ok,
        private mixed $value,
        private array $reasons,
    ) {
    }

    /**
     * @template V
     *
     * @param V $value
     *
     * @return self<V>
     */
    public static function ok(mixed $value): self
    {
        return new self(true, $value, []);
    }

    /**
     * @return self<never>
     */
    public static function fail(Reason ...$reasons): self
    {
        /** @var self<never> $failed */
        $failed = new self(false, null, array_values($reasons));

        return $failed;
    }

    public function isOk(): bool
    {
        return $this->ok;
    }

    /**
     * @return T
     */
    public function value(): mixed
    {
        if ($this->ok === false) {
            throw new \LogicException('Cannot read value of a failed Result.');
        }

        /** @var T */
        return $this->value;
    }

    /**
     * @return list<Reason>
     */
    public function reasons(): array
    {
        return $this->reasons;
    }
}
