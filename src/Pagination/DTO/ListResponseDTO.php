<?php

declare(strict_types=1);

namespace Leads\Core\Pagination\DTO;

final class ListResponseDTO
{
    public function __construct(
        public array $items,
        public PaginationDTO $pagination,
    ) {
    }
}
