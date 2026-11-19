<?php

declare(strict_types=1);

namespace Nexia\Laravel\Directory;

use Illuminate\Database\Eloquent\Model;

/** A basic directory read model on the selected App database connection. */
abstract class DirectoryModel extends Model
{
    protected $primaryKey = 'key';

    public $timestamps = false;
}
