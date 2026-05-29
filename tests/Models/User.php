<?php

namespace Firevel\Api\Tests\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Minimal acting user — only needs to satisfy the Authenticatable type-hint on
 * scopeVisibleBy(); it is never persisted or queried.
 */
class User extends Authenticatable
{
    protected $guarded = [];
}
