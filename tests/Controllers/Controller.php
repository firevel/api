<?php

namespace Firevel\Api\Tests\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Stand-in for a firevel app's App\Http\Controllers\Controller. The generated
 * controller assumes a base that supports $this->middleware() (from
 * Illuminate\Routing\Controller) and $this->authorizeResource() (from
 * AuthorizesRequests) — this fixture documents that contract.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
}
