<?php

namespace Firevel\Api\Tests\Models;

use Firevel\Api\Includable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Has a polymorphic subject and declares its candidate target classes, so the
 * Includable trait can constrain the MorphTo include per type.
 */
class Activity extends Model
{
    use Includable;

    protected $guarded = [];

    /** @var array<string, array<string, array<int, class-string>>> */
    protected $relationships = [
        'subject' => [
            'constraints' => [
                Post::class,
                Video::class,
            ],
        ],
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
