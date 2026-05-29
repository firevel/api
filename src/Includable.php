<?php

namespace Firevel\Api;

use Closure;
use Firevel\Includes\HasIncludes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;

/**
 * Wires firevel/includes together with firevel/filterable, firevel/sortable and
 * the visibleBy() authorization scope.
 *
 * A model using this trait gains withIncludes(), where every eager-loaded
 * relationship is constrained the same way a top-level listing is: scoped to the
 * acting user, filtered, sorted and (optionally) limited per parent — all driven
 * by parameters carried in the include string, e.g.
 *
 *     Article::withIncludes($request->input('include'), $request->user())
 *
 *     ?include=comments(status:active,pending|sort:-created_at|limit:5)
 *
 * The related model must itself declare the matching scopes (Filterable /
 * Sortable / visibleBy) for those constraints to apply; any that are absent are
 * simply skipped, so a relationship to a plain model loads open.
 *
 * Polymorphic (MorphTo) includes are constrained per concrete type via
 * MorphTo::constrain(), but only for the target classes the owning model
 * declares through getRelationshipConstraints() (by default the $relationships
 * property) — Eloquent cannot infer them, and a single closure applied to a
 * MorphTo would otherwise run against every type and fail.
 *
 * @package Firevel\Api
 */
trait Includable
{
    use HasIncludes;

    /**
     * Include parameters reserved for sorting/limiting rather than filtering.
     *
     * @var array<int, string>
     */
    protected array $reservedIncludeParameters = ['sort', 'limit'];

    /**
     * Build the per-relationship constraint applied while eager loading.
     *
     * Overrides the open default from HasIncludes so includes inherit the same
     * visibility/filter/sort policy as a normal listing query.
     *
     * @param  mixed  ...$context  forwarded from withIncludes(); the first
     *                             argument, when present, is the acting user.
     */
    protected function includeConstraints(mixed ...$context): Closure
    {
        $user = $context[0] ?? null;

        return fn (array $parameters): Closure => function ($relationship) use ($parameters, $user) {
            // A MorphTo spans several models, so a single closure would run
            // against every type and fail on those lacking a scope/column.
            // Constrain it per declared type instead.
            if ($relationship instanceof MorphTo) {
                return $this->constrainMorphInclude($relationship, $parameters, $user);
            }

            $this->applyIncludeVisibilityAndFilters($relationship, $parameters, $user);
            $this->applyIncludeOrderingAndLimit($relationship, $parameters);

            return $relationship;
        };
    }

    /**
     * Constrain a polymorphic include per concrete type.
     *
     * MorphTo::constrain() applies a separate callback to each type's eager
     * query, so visibility/filters only touch the types that support them. The
     * candidate classes come from the relation's owning model (its getParent(),
     * so nested morph includes resolve correctly); a relation whose owner
     * declares no constraints is loaded open — applying a single closure across
     * every type would fail, and the types cannot be inferred.
     *
     * @param  mixed  $user
     * @param  array<string, mixed>  $parameters
     */
    protected function constrainMorphInclude(MorphTo $relationship, array $parameters, $user): MorphTo
    {
        $parent = $relationship->getParent();
        $relation = $relationship->getRelationName();

        if (! method_exists($parent, 'relationshipsHasConstraints')
            || ! $parent->relationshipsHasConstraints($relation)) {
            return $relationship;
        }

        $callbacks = [];

        foreach ($parent->getRelationshipConstraints($relation) as $class) {
            $callbacks[$class] = function ($query) use ($parameters, $user) {
                // MorphTo is singular, so only visibility/filters apply — not
                // ordering or a per-parent limit.
                $this->applyIncludeVisibilityAndFilters($query, $parameters, $user);
            };
        }

        return $relationship->constrain($callbacks);
    }

    /**
     * Apply the visibleBy() and filter() scopes to a relationship/type query,
     * skipping whichever the target model does not declare.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<*, *, *>|\Illuminate\Database\Eloquent\Builder<*>  $query
     * @param  array<string, mixed>  $parameters
     * @param  mixed  $user
     */
    protected function applyIncludeVisibilityAndFilters($query, array $parameters, $user): void
    {
        $model = $query->getModel();

        if ($user !== null && method_exists($model, 'scopeVisibleBy')) {
            $query->visibleBy($user);
        }

        $filters = $this->includeFilters($parameters);

        if ($filters !== [] && method_exists($model, 'scopeFilter')) {
            $query->filter($filters);
        }
    }

    /**
     * Apply the sort() scope and a clamped per-parent limit to a relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Relations\Relation<*, *, *>  $relationship
     * @param  array<string, mixed>  $parameters
     */
    protected function applyIncludeOrderingAndLimit($relationship, array $parameters): void
    {
        $model = $relationship->getModel();

        if (isset($parameters['sort']) && method_exists($model, 'scopeSort')) {
            $relationship->sort($parameters['sort']);
        }

        if (isset($parameters['limit'])) {
            $relationship->limit($this->includeLimit($parameters['limit']));
        }
    }

    /**
     * Get the constraint classes declared for a morphTo relationship.
     *
     * Reads them from the $relationships property by default
     * ($relationships[$name]['constraints']); override to compute them. Without
     * an entry the MorphTo include loads open.
     *
     * @return array<int, class-string>
     */
    public function getRelationshipConstraints(string $name): array
    {
        if (! $this->relationshipsHasConstraints($name)) {
            return [];
        }

        return $this->relationships[$name]['constraints'];
    }

    /**
     * Check if a relationship has constraints (used for morphTo relationships).
     *
     * Legacy-compatible hook called on the relation's getParent() to decide
     * whether a MorphTo include can be constrained per concrete type.
     */
    public function relationshipsHasConstraints(string $name): bool
    {
        return ! empty($this->relationships[$name]['constraints']);
    }

    /**
     * Translate the non-reserved include parameters into a firevel/filterable
     * filter set. A comma-separated value becomes an "in" filter (matching any
     * of the values); a single value is matched for equality.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    protected function includeFilters(array $parameters): array
    {
        $filters = [];

        foreach (Arr::except($parameters, $this->reservedIncludeParameters) as $column => $value) {
            $filters[$column] = str_contains((string) $value, ',') ? ['in' => $value] : $value;
        }

        return $filters;
    }

    /**
     * Clamp a client-supplied include limit to a safe range, so a request can
     * never ask for an unbounded number of related rows per parent.
     */
    protected function includeLimit(mixed $limit): int
    {
        $max = property_exists($this, 'maxIncludeLimit') ? $this->maxIncludeLimit : 100;

        return max(1, min((int) $limit, $max));
    }
}
