<?php

declare(strict_types=1);

namespace Nexia\Laravel\Access\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

interface ResourceActionDecisions
{
    /** @param class-string<Model> $modelClass @return array{create: bool} */
    public function forCollection(Authenticatable $user, string $modelClass): array;

    /** @return array{view: bool, update: bool, delete: bool, restore: bool} */
    public function forRecord(Authenticatable $user, Model $record): array;
}
