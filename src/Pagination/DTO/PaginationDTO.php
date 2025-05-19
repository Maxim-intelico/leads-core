<?php

declare(strict_types=1);

namespace Leads\Core\Pagination\DTO;

final readonly class PaginationDTO
{
    public function __construct(
        public int $count,
        public int $total,
        public int $page,
        public int $perPage,
        public int $pages,
    ) {
    }
}
