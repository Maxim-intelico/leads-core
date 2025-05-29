# Lead core bundle

Lead core bundle for the Symfony Framework

## Installation

Install the latest version with

```bash
composer require leads/core
```
<details>
<summary>CommandBus</summary>

```php
<?php

declare(strict_types=1);

use Leads\Core\CommandBus\CommandInterface;

final readonly class ExampleCommand implements CommandInterface
{
}
```
```php
<?php

declare(strict_types=1);

use Leads\Core\CommandBus\CommandValidatorInterface;
use Leads\Core\CommandBus\CommandInterface;
use Leads\Core\CommandBus\AsCommandValidator;

/**
 * @implements CommandValidatorInterface<ExampleCommand>
 */
 #[AsCommandValidator(commandClass: ExampleCommand::class)]
final readonly class Validator1 implements CommandValidatorInterface 
{
    /**
     * @param ExampleCommand $command
     */
    public function validate(CommandInterface $command): void
    {
        // validate command
    }   
}
```
```php
<?php

declare(strict_types=1);

use Leads\Core\CommandBus\HandlerInterface;
use Leads\Core\CommandBus\AsCommandHandler;

/**
 * @implements HandlerInterface<ExampleCommand>
 */
#[AsCommandHandler(commandClass: ExampleCommand::class)]
class ExampleHandler implements HandlerInterface
{
    /**
     * @param ExampleCommand $command
     */
    public function handle(CommandInterface $command)
    {
        // handle command
    }
}
```

</details>

<br>

<details>
<summary>Exception</summary>
<ul>
    <li>Leads\Core\Exception\EntityNotFoundException.php</li>
</ul>
</details>

<br>

<details>
<summary>Pagination</summary>

```php
<?php

declare(strict_types=1);

use Leads\Core\Pagination\Pagination;
use Leads\Core\Pagination\DBALPagination;
use Doctrine\DBAL\Connection;

class ExampleRepository
{
    public function __construct(
        private Connection $connection,
    ) {
    }
    
    public function find(int $page, int $perPage)
    {
        // create Doctrine\DBAL\Query\QueryBuilder $qb
        $pagination = (new Pagination(
            page: 1,
            perPage: 10,
            pagination: new DBALPagination(
                connection: $this->connection,
                qb: $qb,
            ),
        ))->paginate();
    }
}

```

</details>

<br>

<details>
<summary>BaseAction</summary>

    Adds a basic functioon for validating request api and template responses.
    See methods of the BaseAction.php class

</details>
