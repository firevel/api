<?php

namespace Firevel\Api\Tests\Feature;

use Firevel\Api\Tests\Models\Post;
use Firevel\Api\Tests\Transformers\PostTransformer;
use Firevel\Api\Tests\TestCase;

/**
 * Empirical check of what spatie/laravel-fractal's fractal() helper — exactly the
 * call the firevel/generator controller stub makes — does when handed the RAW
 * request "include" string versus the gated dot-paths from
 * Includable::includeNames().
 *
 * The firevel/includes parameter syntax — comments(status:active|sort:-id|limit:1)
 * — collides with Fractal's own separators: the include string is split on
 * top-level commas and on the first colon, so the include NAME gets corrupted and
 * the relationship is silently never embedded.
 */
class FractalParseIncludesTest extends TestCase
{
    private function seededPost(): Post
    {
        $post = Post::create(['title' => 'P1']);
        $post->comments()->create(['body' => 'c1', 'status' => 'active', 'visible' => true]);
        $post->comments()->create(['body' => 'c2', 'status' => 'pending', 'visible' => true]);

        return $post->load('comments');
    }

    /**
     * Transform $post via the same fractal() helper the controller stub calls,
     * asking for whatever $include says.
     *
     * @return array<string, mixed>
     */
    private function fractalArray(Post $post, string|array $include): array
    {
        return fractal($post, new PostTransformer())
            ->parseIncludes($include)
            ->toArray();
    }

    public function test_raw_include_string_with_parameters_drops_the_relationship(): void
    {
        $post = $this->seededPost();

        // This is what `->parseIncludes($request->input('include'))` would pass.
        $data = $this->fractalArray($post, 'comments(status:active|sort:-id|limit:1)');

        // Fractal split "comments(status:active..." on the first ':' into the
        // include name "comments(status", which matches no availableIncludes
        // entry — so the relationship never appears in the payload.
        $this->assertArrayNotHasKey('comments', $data['data']);
    }

    public function test_raw_comma_separated_filter_value_also_breaks(): void
    {
        $post = $this->seededPost();

        $data = $this->fractalArray($post, 'comments(status:active,pending|limit:5)');

        $this->assertArrayNotHasKey('comments', $data['data']);
    }

    public function test_include_names_embeds_the_relationship_correctly(): void
    {
        $post = $this->seededPost();

        // What the README / updated stub passes instead.
        $data = $this->fractalArray($post, Post::includeNames('comments(status:active|sort:-id|limit:1)'));

        $this->assertArrayHasKey('comments', $data['data']);
        $this->assertCount(2, $data['data']['comments']['data']);
    }

    public function test_raw_string_works_only_when_there_are_no_parameters(): void
    {
        $post = $this->seededPost();

        // No parameters => nothing for Fractal to choke on, so raw passing works.
        $data = $this->fractalArray($post, 'comments');

        $this->assertArrayHasKey('comments', $data['data']);
        $this->assertCount(2, $data['data']['comments']['data']);
    }
}
