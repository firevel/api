<?php

namespace Firevel\Api\Tests\Models;

use Firevel\Filterable\Filterable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * A MorphTo target that declares visibleBy() + filter() — its per-type
 * constraint callback should apply both.
 */
class Video extends Model
{
    use Filterable;

    protected $guarded = [];

    /** @var array<string, string> */
    protected $filterable = [
        'status' => 'string',
    ];

    public function scopeVisibleBy($query, Authenticatable $user)
    {
        return $query->where('visible', true);
    }
}
