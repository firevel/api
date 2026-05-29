<?php

namespace Firevel\Api\Tests\Models;

use Firevel\Api\Paginatable;
use Illuminate\Database\Eloquent\Model;

/**
 * A model whose data shape warrants larger pages: it overrides the Paginatable
 * fallbacks via plain properties. Used only to exercise resolvePageSize(), which
 * never touches the database, so no table is required.
 */
class Report extends Model
{
    use Paginatable;

    protected $defaultPageSize = 200;

    protected $maxPageSize = 1000;
}
