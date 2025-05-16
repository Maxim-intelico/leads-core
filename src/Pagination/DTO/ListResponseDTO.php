<?php

declare(strict_types=1);

namespace Leads\Pagination\DTO;

class ListResponseDTO
{
    public function __construct(
        public array $items,
        public PaginationDTO $pagination,
    ) {
    }
}
