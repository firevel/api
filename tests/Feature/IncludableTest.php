<?php

namespace Firevel\Api\Tests\Feature;

use Firevel\Api\Tests\Models\Post;
use Firevel\Api\Tests\Models\User;
use Firevel\Api\Tests\TestCase;

/**
 * Proves the Includable trait composes the REAL firevel/filterable and
 * firevel/sortable traits with a visibleBy() scope and a per-parent limit,
 * driven entirely by the include string.
 */
class IncludableTest extends TestCase
{
    /**
     * Two posts; comments tagged so each constraint excludes a distinct row.
     *
     * @return array<string, \Firevel\Api\Tests\Models\Post|\Firevel\Api\Tests\Models\Comment>
     */
    private function seedComments(): array
    {
        $p1 = Post::create(['title' => 'P1']);
        $c1 = $p1->comments()->create(['body' => 'c1', 'status' => 'active', 'visible' => true]);
        $c2 = $p1->comments()->create(['body' => 'c2', 'status' => 'pending', 'visible' => true]);
        $p1->comments()->create(['body' => 'c3', 'status' => 'closed', 'visible' => true]);   // filtered out
        $p1->comments()->create(['body' => 'c4', 'status' => 'active', 'visible' => false]);  // hidden by visibleBy

        $p2 = Post::create(['title' => 'P2']);
        $c5 = $p2->comments()->create(['body' => 'c5', 'status' => 'active', 'visible' => true]);
        $p2->comments()->create(['body' => 'c6', 'status' => 'pending', 'visible' => false]); // hidden by visibleBy

        return compact('p1', 'c1', 'c2', 'c5', 'p2');
    }

    public function test_includes_compose_visibleby_filterable_sortable_and_limit(): void
    {
        ['p1' => $p1, 'c2' => $c2, 'c5' => $c5, 'p2' => $p2] = $this->seedComments();

        // in(active,pending) + visibleBy(visible) + sort(-id) + limit(1), per parent.
        $posts = Post::withIncludes('comments(status:active,pending|sort:-id|limit:1)', new User())
            ->orderBy('id')
            ->get();

        $p1Comments = $posts->firstWhere('id', $p1->id)->comments;
        $this->assertCount(1, $p1Comments);                       // per-parent limit
        $this->assertSame($c2->id, $p1Comments->first()->id);     // sort -id picks the newest of {c1, c2}

        $p2Comments = $posts->firstWhere('id', $p2->id)->comments;
        $this->assertCount(1, $p2Comments);
        $this->assertSame($c5->id, $p2Comments->first()->id);     // c6 excluded by visibleBy
        $this->assertTrue($p2Comments->every->visible);
    }

    public function test_a_comma_value_filters_with_in_a_single_value_with_equality(): void
    {
        $this->seedComments();

        // Single value => equality: only the (visible) "closed" comment loads.
        $post = Post::withIncludes('comments(status:closed)', new User())->first();

        $this->assertCount(1, $post->comments);
        $this->assertSame('closed', $post->comments->first()->status);
    }

    public function test_without_a_user_visibility_is_skipped_but_filtering_still_applies(): void
    {
        $this->seedComments();

        // No user passed: visibleBy is not applied, but the status filter is —
        // so both the visible (c1) and hidden (c4) "active" comments load.
        $post = Post::withIncludes('comments(status:active)')->orderBy('id')->first();

        $this->assertCount(2, $post->comments);
        $this->assertSame(['active', 'active'], $post->comments->pluck('status')->all());
    }

    public function test_include_names_are_exposed_for_the_transformer_layer(): void
    {
        // Inherited from HasIncludes: clean dot-paths with parameters stripped,
        // safe to hand to Fractal's parseIncludes().
        $this->assertSame(['comments'], Post::includeNames('comments(status:active|limit:5)'));
    }
}
