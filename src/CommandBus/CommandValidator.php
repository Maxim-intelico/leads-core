<?php

declare(strict_types=1);

namespace Leads\Core\CommandBus;

use Psr\Container\ContainerInterface;

final readonly class CommandValidator
{
    /**
     * @param ContainerInterface $validators service locator keyed by validator service id
     * @param array<class-string<CommandInterface>, list<string>> $validatorsMap command class-string => list of validator service ids
     */
    public function __construct(
        private ContainerInterface $validators,
        private array $validatorsMap,
    ) {
    }

    public function validate(CommandInterface $command): void
    {
        foreach ($this->validatorsMap[$command::class] ?? [] as $id) {
            /** @var CommandValidatorInterface $validator */
            $validator = $this->validators->get($id);
            $validator->validate($command);
        }
    }
}
