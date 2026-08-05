<?php

declare(strict_types=1);

namespace Leads\Core\Pagination;

use ClickHouseDB\Client;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Requires the optional smi2/phpclickhouse package (`composer require smi2/phpclickhouse`).
 *
 * The QueryBuilder must use named parameters (`:name`) — positional `?`
 * placeholders are not substituted by the ClickHouse client.
 */
final readonly class ClickHousePagination implements PaginationInterface
{
    public function __construct(
        private Client $client,
        private QueryBuilder $qb,
    ) {
    }

    public function getItems(int $offset, int $limit): array
    {
        $targetQb = (clone $this->qb)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $this->client
            ->select($targetQb->getSQL(), $targetQb->getParameters())
            ->rows();
    }

    public function getTotal(): int
    {
        $targetQb = clone $this->qb;
        $targetQb->resetOrderBy();

        return (int)$this->client
            ->select('SELECT count() AS total FROM (' . $targetQb->getSQL() . ')', $targetQb->getParameters())
            ->fetchOne('total');
    }
}
