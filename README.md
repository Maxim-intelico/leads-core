# leads/core

Shared core bundle for Leads projects built on the Symfony Framework. It provides HTTP payload mapping into request DTOs, DBAL-based pagination, API request validation helpers and base controller utilities.

## Requirements

- PHP >= 8.4
- Symfony 8.1+
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

## HTTP payload mapping

`Leads\Core\Payload` maps an HTTP request into an immutable request DTO declared with attributes — no serializer, no per-endpoint glue code. Put `#[Payload]` on a controller argument and the bundle's value resolver builds, casts and validates the DTO:

```php
use Leads\Core\Payload\Attribute\Clamp;
use Leads\Core\Payload\Attribute\FromHeader;
use Leads\Core\Payload\Attribute\FromPath;
use Leads\Core\Payload\Attribute\FromQuery;
use Leads\Core\Payload\Attribute\Payload;

final readonly class ListOrdersRequest
{
    public function __construct(
        #[FromQuery]
        #[Clamp(1, 100)]
        public int $limit = 20,
        #[FromQuery]
        public ?\DateTimeImmutable $fromDate = null,
        #[FromPath]
        public string $projectId,
        #[FromHeader('X-Request-Source')]
        public ?string $requestSource = null,
    ) {
    }
}

final class ListOrdersAction
{
    public function __invoke(#[Payload] ListOrdersRequest $request): JsonResponse
    {
        // $request is fully cast and validated here
    }
}
```

Every constructor parameter declares its source: `#[FromBody]`, `#[FromQuery]`, `#[FromPath]` or `#[FromHeader('Name')]`. Values are cast to the parameter type (scalars, `\DateTimeImmutable`, backed enums, nested DTOs, collections via a `@param list<...>` docblock) and then run through the Symfony Validator (constraint attributes on the DTO). Additional behavior attributes:

- `#[HttpPayload(groups: [...], maxBodyBytes: ...)]` (class level) — validation groups and body size limit
- `#[Clamp(min, max)]` — clamp an int into a range
- `#[FallbackTo(value)]` — value to use when casting fails (except type mismatches)
- `#[Split(separator: ',')]` — split a query string into a scalar list
- `#[MaxItems(n)]` — collection size limit (default 1000)
- `#[FreeForm]` — accept an arbitrary array structure as-is
- `#[Normalized]` — case-insensitive enum matching

Misconfiguration (missing source attribute, unsupported type, nested constraints without `#[Assert\Valid]`, …) fails fast with a `\LogicException` when the plan is compiled — not at request time.

Errors are reported per field with JSON-pointer-style paths (`body/items/0/quantity`, `query/limit`): casting failures throw a 400, validator violations a 422, plus 413 for oversized and 400 for malformed bodies. All of these implement `Leads\Core\Payload\Error\HttpProblemException`, and the bundled `PayloadExceptionListener` turns them into RFC 9457 `application/problem+json` responses automatically. `StateConflictException` (409) is available for domain-level conflicts in the same format.

The component is extensible from the consuming project: implement `Leads\Core\Payload\Cast\ValueCaster` or `Leads\Core\Payload\Source\ValueSource` in your application and autoconfiguration tags it (`leads_core.payload.caster` / `leads_core.payload.source`) — no configuration needed.

You can also call the mapper directly instead of using the resolver: inject `Leads\Core\Payload\PayloadMapper` and call `map(MyRequest::class, $request)`.

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
