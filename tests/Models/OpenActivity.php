<?php

namespace Firevel\Api\Tests\Models;

use Firevel\Api\Includable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Same polymorphic subject as Activity but declares no $includeMorphTypes, so
 * its MorphTo include must load open (unconstrained) rather than crash.
 */
class OpenActivity extends Model
{
    use Includable;

    protected $table = 'activities';

    protected $guarded = [];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
