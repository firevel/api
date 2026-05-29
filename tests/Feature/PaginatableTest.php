<?php

namespace Firevel\Api\Tests\Feature;

use Firevel\Api\Tests\Models\Post;
use Firevel\Api\Tests\Models\Report;
use Firevel\Api\Tests\TestCase;

/**
 * Proves the Paginatable trait resolves a per-model page size: a blank request
 * falls back to the model default, an in-range value is honoured, anything above
 * the max is clamped down, anything below 1 is clamped up — and model properties
 * override the 20 / 100 fallbacks. apiPaginate() feeds the resolved size to
 * Laravel's paginator.
 */
class PaginatableTest extends TestCase
{
    public function test_blank_request_falls_back_to_the_default(): void
    {
        $this->assertSame(20, (new Post())->resolvePageSize(null));
        $this->assertSame(20, (new Post())->resolvePageSize(''));
    }

    public function test_value_within_range_is_honoured(): void
    {
        $this->assertSame(15, (new Post())->resolvePageSize(15));
        $this->assertSame(15, (new Post())->resolvePageSize('15')); // query strings arrive as strings
    }

    public function test_value_above_the_max_is_clamped_down(): void
    {
        $this->assertSame(100, (new Post())->resolvePageSize(500));
    }

    public function test_value_below_one_is_clamped_up(): void
    {
        $this->assertSame(1, (new Post())->resolvePageSize(0));
        $this->assertSame(1, (new Post())->resolvePageSize(-5));
    }

    public function test_model_properties_override_the_fallbacks(): void
    {
        $report = new Report();

        $this->assertSame(200, $report->resolvePageSize(null));   // overridden default
        $this->assertSame(750, $report->resolvePageSize(750));    // within the raised max
        $this->assertSame(1000, $report->resolvePageSize(5000));  // clamped to the raised max
    }

    public function test_scope_paginates_with_the_resolved_size(): void
    {
        foreach (range(1, 30) as $i) {
            Post::create(['title' => "P{$i}"]);
        }

        $explicit = Post::apiPaginate(5);
        $this->assertSame(5, $explicit->perPage());
        $this->assertCount(5, $explicit->items());

        $fallback = Post::apiPaginate(null);
        $this->assertSame(20, $fallback->perPage());
    }
}
