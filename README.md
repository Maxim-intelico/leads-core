# leads/core

Shared core bundle for Leads projects built on the Symfony Framework. It provides a lightweight command bus, DBAL-based pagination, API request validation helpers and base controller utilities.

## Requirements

- PHP >= 8.3
- Symfony 7.2+ or 8.x (the package resolves to Symfony 8.x on PHP >= 8.4)
- Doctrine DBAL ^4.0 (used by the pagination component)

## Installation

```bash
composer require leads/core
```

Register the bundle in `config/bundles.php` (done automatically if you use Symfony Flex):

```php
return [
    // ...
    Leads\Core\LeadsCoreBundle::class => ['all' => true],
];
```

## Command bus

A minimal synchronous command bus. Handlers and validators are discovered by PHP attributes and wired at container compile time; they are instantiated lazily through a service locator — only when their command is actually dispatched.

### 1. Define a command

```php
use Leads\Core\CommandBus\CommandInterface;

final readonly class CreateOrderCommand implements CommandInterface
{
    public function __construct(
        public string $customerId,
        public int $amount,
    ) {
    }
}
```

### 2. Define a handler

Exactly one handler per command, marked with `#[AsCommandHandler]`:

```php
use Leads\Core\CommandBus\AsCommandHandler;
use Leads\Core\CommandBus\CommandInterface;
use Leads\Core\CommandBus\HandlerInterface;

/**
 * @implements HandlerInterface<CreateOrderCommand>
 */
#[AsCommandHandler(commandClass: CreateOrderCommand::class)]
final readonly class CreateOrderHandler implements HandlerInterface
{
    /**
     * @param CreateOrderCommand $command
     */
    public function handle(CommandInterface $command)
    {
        // create the order, return whatever the caller needs (e.g. an id)
    }
}
```

### 3. Define validators (optional)

Any number of validators per command, marked with `#[AsCommandValidator]`. All matching validators run before the handler; a validator reports failure by throwing `CommandValidatorException`:

```php
use Leads\Core\CommandBus\AsCommandValidator;
use Leads\Core\CommandBus\CommandInterface;
use Leads\Core\CommandBus\CommandValidatorException;
use Leads\Core\CommandBus\CommandValidatorInterface;

/**
 * @implements CommandValidatorInterface<CreateOrderCommand>
 */
#[AsCommandValidator(commandClass: CreateOrderCommand::class)]
final readonly class CreateOrderAmountValidator implements CommandValidatorInterface
{
    /**
     * @param CreateOrderCommand $command
     */
    public function validate(CommandInterface $command): void
    {
        if ($command->amount <= 0) {
            throw new CommandValidatorException('Amount must be positive.');
        }
    }
}
```

### 4. Dispatch

Inject `CommandBus` and call `handle()`:

```php
use Leads\Core\CommandBus\CommandBus;

final readonly class OrderService
{
    public function __construct(
        private CommandBus $commandBus,
    ) {
    }

    public function createOrder(string $customerId, int $amount): mixed
    {
        return $this->commandBus->handle(new CreateOrderCommand($customerId, $amount));
    }
}
```

Exceptions thrown by `CommandBus::handle()`:

- `Leads\Core\CommandBus\CommandValidatorException` — a validator rejected the command (default code 400)
- `Leads\Core\CommandBus\HandlerNotFoundException` — no handler is registered for the command class

## Pagination

Page-based pagination over a Doctrine DBAL `QueryBuilder`. The `Leads\Core\Pagination` namespace is excluded from container autowiring — instantiate the classes manually:

```php
use Doctrine\DBAL\Connection;
use Leads\Core\Pagination\DBALPagination;
use Leads\Core\Pagination\DTO\ListResponseDTO;
use Leads\Core\Pagination\Pagination;

final readonly class OrderRepository
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findPaginated(int $page, int $perPage): ListResponseDTO
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('id', 'customer_id', 'amount')
            ->from('orders')
            ->orderBy('id', 'DESC');

        return (new Pagination(
            page: $page,
            perPage: $perPage,
            pagination: new DBALPagination(
                connection: $this->connection,
                qb: $qb,
            ),
        ))->paginate();
    }
}
```

`paginate()` returns a `ListResponseDTO`:

- `items` — the rows for the requested page (`fetchAllAssociative()` result)
- `pagination` — a `PaginationDTO` with `count` (items on this page), `total` (total rows), `page`, `perPage` and `pages` (total page count)

The total is computed by wrapping your query in a `COUNT(*)` subquery (with `ORDER BY` stripped), so it stays correct for queries with `GROUP BY` or `DISTINCT`. `Pagination` throws `\InvalidArgumentException` if `page` or `perPage` is less than 1.

To paginate a different data source, implement `Leads\Core\Pagination\PaginationInterface` (`getItems(int $offset, int $limit): array` and `getTotal(): int`) and pass it instead of `DBALPagination`.

### ClickHouse

`ClickHousePagination` runs the same DBAL `QueryBuilder`-built query against ClickHouse via the `smi2/phpclickhouse` client. The package is an optional dependency — install it in your project first:

```bash
composer require smi2/phpclickhouse
```

```php
use ClickHouseDB\Client;
use Leads\Core\Pagination\ClickHousePagination;
use Leads\Core\Pagination\Pagination;

return (new Pagination(
    page: $page,
    perPage: $perPage,
    pagination: new ClickHousePagination(
        client: $client, // ClickHouseDB\Client
        qb: $qb,
    ),
))->paginate();
```

Constraints:

- The `QueryBuilder` must use **named parameters** (`:name`) — positional `?` placeholders are not substituted by the ClickHouse client.
- The SQL (including `LIMIT`/`OFFSET` syntax and identifier quoting) is rendered by the DBAL platform of the connection the `QueryBuilder` was created from, so use a connection whose platform produces ClickHouse-compatible SQL (MySQL and PostgreSQL platforms are fine).

## API validation and base controller

`ApiValidator` wraps the Symfony Validator: it validates an object against its constraint attributes and throws `ApiValidationException` if there are violations. The exception exposes the violations as `getErrors()` — a list of `['property' => ..., 'message' => ...]` pairs, also JSON-encoded into the exception message.

Controllers can extend `BaseAction` to get validation plus JSON response helpers:

```php
use Leads\Core\Action\BaseAction;
use Symfony\Component\HttpFoundation\JsonResponse;

final class CreateOrderAction extends BaseAction
{
    public function __invoke(CreateOrderRequest $request): JsonResponse
    {
        $this->validate($request); // throws ApiValidationException on violations

        $id = /* ... */;

        return $this->create201Response($id); // {"id": "..."}
    }
}
```

Available response helpers:

- `create200Response(array $data)` — 200 with a JSON body
- `create201Response(string $id)` — 201 with `{"id": ...}`
- `create201ContentResponse(array $response)` — 201 with a custom body
- `create201EmptyResponse()` — 201 with an empty body
- `create204Response()` — 204
- `create400Response(string $message)` — 400 with a message
- `createCustomResponse(array $data, int $status)` — any status

`ApiValidatorInterface` is autowired to `ApiValidator` by the bundle; you can also inject it directly into your own services.

## Exceptions

The `Leads\Core\Exception` namespace provides ready-to-use domain exceptions. All of them extend `\RuntimeException` and carry an HTTP-like status code in `getCode()`, so an exception listener can map them to responses directly. Each constructor accepts an optional custom message, code override and `previous` throwable:

- `Leads\Core\Exception\EntityNotFoundException` — `Entity not found.`, code 404; for missing entities in repositories/handlers
- `Leads\Core\Exception\UserNotFoundException` — `User not found.`, code 404
- `Leads\Core\Exception\AccessDeniedException` — `Access Denied.`, code 403

```php
use Leads\Core\Exception\EntityNotFoundException;

$order = $repository->find($id)
    ?? throw new EntityNotFoundException(sprintf('Order "%s" not found.', $id));
```

## License

MIT
