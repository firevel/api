<?php

namespace Firevel\Api\Tests\Models;

use Firevel\Filterable\Filterable;
use Firevel\Sortable\Sortable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Related model wired exactly like a generated firevel model: the real
 * Filterable + Sortable traits plus a visibleBy() scope. The Includable trait on
 * Post drives all three through the eager-load constraint.
 */
class Comment extends Model
{
    use Filterable;
    use Sortable;

    protected $guarded = [];

    /**
     * Columns exposed to firevel/filterable (column => type).
     *
     * @var array<string, string>
     */
    protected $filterable = [
        'status' => 'string',
    ];

    /**
     * Columns exposed to firevel/sortable.
     *
     * @var array<int, string>
     */
    protected $sortable = ['id'];

    /**
     * Mirrors the generated scopeVisibleBy(): restricts to rows the user may see.
     */
    public function scopeVisibleBy($query, Authenticatable $user)
    {
        return $query->where('visible', true);
    }
}
