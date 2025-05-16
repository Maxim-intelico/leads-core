<?php

declare(strict_types=1);

namespace Leads\Pagination;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

final readonly class DBALPagination implements PaginationInterface
{
    public function __construct(
        private Connection $connection,
        private QueryBuilder $qb,
    ) {
    }

    public function getItems(int $offset, int $limit): array
    {
        return (clone $this->qb)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();
    }

    public function getTotal(): int
    {
        $targetQb = clone $this->qb;
        $targetQb->resetOrderBy();

        return (int)$this->connection
            ->createQueryBuilder()
            ->select('COUNT(*)')
            ->from("({$targetQb->getSQL()})", 'tmp')
            ->setParameters($targetQb->getParameters(), $targetQb->getParameterTypes())
            ->executeQuery()
            ->fetchOne();
    }
}
