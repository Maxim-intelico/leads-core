<?php

declare(strict_types=1);

namespace Leads\Core\Pagination;

use Leads\Core\Pagination\DTO\ListResponseDTO;
use Leads\Core\Pagination\DTO\PaginationDTO;

final readonly class Pagination
{
    public function __construct(
        private int $page,
        private int $perPage,
        private PaginationInterface $pagination,
    ) {
    }

    public function paginate(): ListResponseDTO
    {
        if ($this->page < 1) {
            throw new \InvalidArgumentException('Page must be greater than 0.');
        }
        if ($this->perPage < 1) {
            throw new \InvalidArgumentException('PerPage must be greater than 0.');
        }

        $offset = ($this->page * $this->perPage) - $this->perPage;

        $items = $this->pagination->getItems(
            offset: $offset,
            limit: $this->perPage,
        );
        $total = $this->pagination->getTotal();

        return new ListResponseDTO(
            items: $items,
            pagination: new PaginationDTO(
                count: count($items),
                total: $total,
                page: $this->page,
                perPage: $this->perPage,
                pages: (int)ceil($total / $this->perPage),
            ),
        );
    }
}
