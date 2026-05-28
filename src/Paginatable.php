<?php

namespace Firevel\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Adds an apiPaginate() query scope that paginates a listing using a per-model
 * page-size policy, clamping the client-requested size to a safe range.
 *
 *     Article::filter($request->input('filter'))
 *         ->sort($request->input('sort'))
 *         ->apiPaginate($request->input('page.size'));
 *
 * Page-size limits are a property of the model's data shape, so they live on the
 * model: declare $defaultPageSize / $maxPageSize to override the fallbacks (20 /
 * 100). A model holding small rows can allow larger pages; a heavy one can cap
 * lower. Mirrors the per-model $maxIncludeLimit convention from Includable.
 *
 * @package Firevel\Api
 */
trait Paginatable
{
    /**
     * Fallback page size used when the request omits one and the model declares
     * no $defaultPageSize.
     */
    protected static int $fallbackDefaultPageSize = 20;

    /**
     * Fallback ceiling used when the model declares no $maxPageSize.
     */
    protected static int $fallbackMaxPageSize = 100;

    /**
     * Paginate the query using the resolved, clamped page size.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, *>
     */
    public function scopeApiPaginate(Builder $query, mixed $perPage = null): LengthAwarePaginator
    {
        return $query->paginate($this->resolvePageSize($perPage));
    }

    /**
     * Clamp a client-supplied page size to this model's safe range, falling back
     * to the model's default when none is supplied.
     */
    public function resolvePageSize(mixed $requested): int
    {
        $default = property_exists($this, 'defaultPageSize') ? $this->defaultPageSize : static::$fallbackDefaultPageSize;
        $max = property_exists($this, 'maxPageSize') ? $this->maxPageSize : static::$fallbackMaxPageSize;

        if (blank($requested)) {
            return $default;
        }

        return max(1, min((int) $requested, $max));
    }
}
