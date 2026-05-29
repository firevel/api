<?php

namespace Firevel\Api\Tests\Transformers;

use Firevel\Api\Tests\Models\Comment;
use League\Fractal\TransformerAbstract;

class CommentTransformer extends TransformerAbstract
{
    public function transform(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'status' => $comment->status,
        ];
    }
}
