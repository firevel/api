<?php

namespace Firevel\Api\Tests\Transformers;

use Firevel\Api\Tests\Models\Post;
use League\Fractal\Resource\Collection;
use League\Fractal\TransformerAbstract;

class PostTransformer extends TransformerAbstract
{
    protected array $availableIncludes = ['comments'];

    public function transform(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
        ];
    }

    public function includeComments(Post $post): Collection
    {
        return $this->collection($post->comments, new CommentTransformer());
    }
}
