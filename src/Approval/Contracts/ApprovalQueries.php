<?php

declare(strict_types=1);

namespace Nexia\Approval\Contracts;

use Nexia\Organization\Contracts\LegalEntity;

interface ApprovalQueries
{
    /** @return array{data: list<array<string, mixed>>, current_page: int, last_page: int, per_page: int, total: int} */
    public function paginate(LegalEntity $legalEntity, array $conditions, array $orders, int $perPage, int $page): array;

    /** @return array<string, mixed>|null */
    public function first(LegalEntity $legalEntity, array $conditions, array $orders): ?array;
}
