<?php

namespace Firevel\Api\Tests\Controllers;

use Firevel\Api\Tests\Models\Post;
use Firevel\Api\Tests\Requests\Post\DestroyPost;
use Firevel\Api\Tests\Requests\Post\IndexPosts;
use Firevel\Api\Tests\Requests\Post\ShowPost;
use Firevel\Api\Tests\Requests\Post\StorePost;
use Firevel\Api\Tests\Requests\Post\UpdatePost;
use Firevel\Api\Tests\Transformers\PostTransformer;
use Illuminate\Support\Facades\Response;

/**
 * Byte-for-byte the body the firevel/generator controller stub emits (modulo the
 * test namespace), so this suite breaks if a firevel/api trait drifts away from
 * the generated usage: filter → visibleBy → withIncludes → sort → apiPaginate,
 * with Fractal fed the gated includeNames().
 */
class PostsController extends Controller
{
    /**
     * @var PostTransformer
     */
    protected $transformer;

    public function __construct(PostTransformer $transformer)
    {
        $this->middleware('auth:api');

        $this->transformer = $transformer;

        $this->authorizeResource(Post::class);
    }

    public function index(IndexPosts $request)
    {
        $posts = Post::filter($request->input('filter'))
            ->visibleBy($request->user())
            ->withIncludes($request->input('include'), $request->user())
            ->sort($request->input('sort'))
            ->apiPaginate($request->input('page.size'));

        return fractal($posts, $this->transformer)
            ->parseIncludes(Post::includeNames($request->input('include')))
            ->respond();
    }

    public function store(StorePost $request)
    {
        $post = Post::create($request->validated());

        return fractal($post, $this->transformer)
            ->parseIncludes(Post::includeNames($request->input('include')))
            ->respond(201);
    }

    public function show(ShowPost $request, Post $post)
    {
        return fractal($post, $this->transformer)
            ->parseIncludes(Post::includeNames($request->input('include')))
            ->respond();
    }

    public function update(UpdatePost $request, Post $post)
    {
        $post->fill($request->validated())
            ->save();

        return fractal($post, $this->transformer)
            ->parseIncludes(Post::includeNames($request->input('include')))
            ->respond();
    }

    public function destroy(DestroyPost $request, Post $post)
    {
        $post->delete();

        return Response::json(null, 204);
    }
}
