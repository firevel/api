<?php

namespace Firevel\Api\Tests\Feature;

use Firevel\Api\Tests\Models\Activity;
use Firevel\Api\Tests\Models\OpenActivity;
use Firevel\Api\Tests\Models\Post;
use Firevel\Api\Tests\Models\User;
use Firevel\Api\Tests\Models\Video;
use Firevel\Api\Tests\TestCase;

/**
 * Proves MorphTo includes are constrained per concrete type via
 * MorphTo::constrain(): the visibleBy/filter scopes touch only the types that
 * declare them, a scope-less type still loads, and an undeclared morph relation
 * loads open instead of crashing.
 */
class MorphToIncludeTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function seedSubjects(): array
    {
        $visibleVideo = Video::create(['title' => 'visible', 'status' => 'active', 'visible' => true]);
        $hiddenVideo = Video::create(['title' => 'hidden', 'status' => 'active', 'visible' => false]);
        $post = Post::create(['title' => 'a post']); // no visibleBy/filter scopes

        $a1 = Activity::create();
        $a1->subject()->associate($visibleVideo)->save();

        $a2 = Activity::create();
        $a2->subject()->associate($hiddenVideo)->save();

        $a3 = Activity::create();
        $a3->subject()->associate($post)->save();

        return compact('visibleVideo', 'hiddenVideo', 'post', 'a1', 'a2', 'a3');
    }

    public function test_morph_to_include_is_constrained_per_type(): void
    {
        ['visibleVideo' => $visibleVideo, 'post' => $post, 'a1' => $a1, 'a2' => $a2, 'a3' => $a3]
            = $this->seedSubjects();

        $activities = Activity::withIncludes('subject', new User())
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        // Video declares visibleBy: the visible one loads, the hidden one is
        // excluded by the per-type constraint (subject resolves to null).
        $this->assertTrue($activities[$a1->id]->relationLoaded('subject'));
        $this->assertNotNull($activities[$a1->id]->subject);
        $this->assertSame($visibleVideo->id, $activities[$a1->id]->subject->id);

        $this->assertNull($activities[$a2->id]->subject);

        // Post declares no visibleBy/filter: its callback applies nothing, so it
        // loads open — and crucially does not error.
        $this->assertNotNull($activities[$a3->id]->subject);
        $this->assertSame($post->id, $activities[$a3->id]->subject->id);
    }

    public function test_morph_to_include_without_declared_types_loads_open(): void
    {
        ['hiddenVideo' => $hiddenVideo, 'a2' => $a2] = $this->seedSubjects();

        // OpenActivity declares no $includeMorphTypes, so the polymorphic include
        // is left unconstrained: even the hidden video loads (visibleBy skipped).
        $activity = OpenActivity::withIncludes('subject', new User())->find($a2->id);

        $this->assertNotNull($activity->subject);
        $this->assertSame($hiddenVideo->id, $activity->subject->id);
    }
}
