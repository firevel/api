<?php

namespace Firevel\Api\Tests\Feature;

use Firevel\Api\Tests\Controllers\PostsController;
use Firevel\Api\Tests\Models\Post;
use Firevel\Api\Tests\Models\User;
use Firevel\Api\Tests\TestCase;
use Illuminate\Support\Facades\Gate;

/**
 * End-to-end proof that a firevel/generator api-resource works against firevel/api:
 * a model, controller and FormRequests shaped exactly like the generated stubs are
 * routed and driven over HTTP, exercising the whole chain — filter → visibleBy →
 * withIncludes → sort → apiPaginate — plus Fractal's gated includeNames().
 */
class GeneratedResourceTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        // The generated controller guards with auth:api; give the harness that guard.
        $app['config']->set('auth.guards.api', ['driver' => 'session', 'provider' => 'users']);
        $app['config']->set('auth.providers.users.model', User::class);
    }

    protected function defineRoutes($router)
    {
        // SubstituteBindings (normally in the "api" group) powers the implicit
        // {post} -> Post route-model binding the show/update/destroy actions rely on.
        $router->apiResource('posts', PostsController::class)
            ->middleware(\Illuminate\Routing\Middleware\SubstituteBindings::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // authorizeResource() in the controller delegates to policies; allow all.
        Gate::before(fn () => true);

        $this->actingAs(new User(), 'api');
    }

    public function test_index_filters_includes_sorts_and_paginates(): void
    {
        $alpha = Post::create(['title' => 'Alpha']);
        $alpha->comments()->create(['body' => 'a', 'status' => 'active', 'visible' => true]);
        $alpha->comments()->create(['body' => 'b', 'status' => 'closed', 'visible' => true]);   // status filter drops it
        $alpha->comments()->create(['body' => 'c', 'status' => 'active', 'visible' => false]);  // visibleBy drops it
        Post::create(['title' => 'Beta']);

        $response = $this->getJson('/posts?include=comments(status:active)&sort=-id&page[size]=10');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        // sort=-id => newest (Beta) first.
        $response->assertJsonPath('data.0.title', 'Beta');
        $response->assertJsonPath('data.1.title', 'Alpha');

        // withIncludes constrained the eager load: only the active + visible comment survives.
        $response->assertJsonCount(0, 'data.0.comments.data');
        $response->assertJsonCount(1, 'data.1.comments.data');
        $response->assertJsonPath('data.1.comments.data.0.status', 'active');
    }

    public function test_filter_narrows_the_listing(): void
    {
        Post::create(['title' => 'Keep']);
        Post::create(['title' => 'Drop']);

        $response = $this->getJson('/posts?filter[title]=Keep');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Keep');
    }

    public function test_page_size_is_honoured(): void
    {
        foreach (range(1, 30) as $i) {
            Post::create(['title' => "P{$i}"]);
        }

        $this->getJson('/posts?page[size]=5')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_page_number_uses_the_json_api_parameter(): void
    {
        foreach (range(1, 30) as $i) {
            Post::create(['title' => "P{$i}"]);
        }

        // JSON:API page[number] must select the page; Laravel's default resolver
        // only reads a flat ?page=, so this proves the package's resolver is active.
        $response = $this->getJson('/posts?sort=id&page[size]=5&page[number]=2')
            ->assertOk()
            ->assertJsonCount(5, 'data');

        // Page 2 at size 5, sorted by id => P6..P10.
        $response->assertJsonPath('data.0.title', 'P6');
        $response->assertJsonPath('data.4.title', 'P10');
    }

    public function test_includes_are_omitted_when_not_requested(): void
    {
        $post = Post::create(['title' => 'Alpha']);
        $post->comments()->create(['body' => 'a', 'status' => 'active', 'visible' => true]);

        $response = $this->getJson('/posts');

        $response->assertOk();
        $this->assertArrayNotHasKey('comments', $response->json('data.0'));
    }

    public function test_show_returns_a_single_resource_with_includes(): void
    {
        $post = Post::create(['title' => 'Alpha']);
        $post->comments()->create(['body' => 'a', 'status' => 'active', 'visible' => true]);

        $this->getJson("/posts/{$post->id}?include=comments")
            ->assertOk()
            ->assertJsonPath('data.title', 'Alpha')
            ->assertJsonCount(1, 'data.comments.data');
    }

    public function test_store_creates_a_resource(): void
    {
        $this->postJson('/posts', ['title' => 'Created'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Created');

        $this->assertDatabaseHas('posts', ['title' => 'Created']);
    }

    public function test_store_validates_input(): void
    {
        $this->postJson('/posts', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function test_update_modifies_a_resource(): void
    {
        $post = Post::create(['title' => 'Old']);

        $this->putJson("/posts/{$post->id}", ['title' => 'New'])
            ->assertOk()
            ->assertJsonPath('data.title', 'New');

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'New']);
    }

    public function test_destroy_removes_a_resource(): void
    {
        $post = Post::create(['title' => 'Doomed']);

        $this->deleteJson("/posts/{$post->id}")->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
