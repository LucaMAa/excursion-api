<?php

declare(strict_types=1);

namespace App\Dto;

final class QueryDto
{
    public function __construct(
        private readonly int $limit = 10,
        private readonly int $offset = 0,
    ) {
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }
}
