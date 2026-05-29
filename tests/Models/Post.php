<?php

namespace Firevel\Api\Tests\Models;

use Firevel\Api\Includable;
use Firevel\Api\Paginatable;
use Firevel\Filterable\Filterable;
use Firevel\Sortable\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Mirrors a firevel/generator api-resource model: the same trait set
 * (Includable, Paginatable, Sortable, Filterable) plus a scopeVisibleBy(), so the
 * generated controller chain — filter → visibleBy → withIncludes → sort →
 * apiPaginate — runs against it exactly as in a generated app.
 */
class Post extends Model
{
    use HasFactory;
    use Includable;
    use Paginatable;
    use Sortable;
    use Filterable;

    /**
     * @var array<int, string>
     */
    protected $fillable = ['title'];

    /**
     * @var array<int, string>
     */
    protected $sortable = ['id', 'title'];

    /**
     * @var array<string, string>
     */
    protected $filterable = ['title' => 'string'];

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    /**
     * Generated default: no top-level restriction (per-relation visibility is
     * still enforced on the related models).
     */
    public function scopeVisibleBy($query, Authenticatable $user)
    {
        return $query;
    }
}
