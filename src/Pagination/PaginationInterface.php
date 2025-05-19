<?php

declare(strict_types=1);

namespace Leads\Core\Pagination;

interface PaginationInterface
{
    public function getItems(int $offset, int $limit): array;

    public function getTotal(): int;
}
