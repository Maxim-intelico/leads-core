<?php

declare(strict_types=1);

namespace Leads\Core\CommandBus;

final readonly class CommandValidator
{
    public function __construct(
        /** @var array<class-string<CommandInterface>, CommandValidatorInterface[]> */
        private array $validatorsMap,
    ) {
    }

    public function validate(CommandInterface $command): void
    {
        $validators = $this->validatorsMap[$command::class] ?? [];
        foreach ($validators as $validator) {
            $validator->validate($command);
        }
    }
}
