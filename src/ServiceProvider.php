<?php

namespace Firevel\Api;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        $this->resolveJsonApiPageNumber();
    }

    /**
     * Teach Laravel's paginator to read the page number from the JSON:API style
     * page[number] parameter (alongside apiPaginate()'s page[size]), while still
     * accepting a flat ?page=N. Laravel's default resolver only understands the
     * flat form, so without this every page[number] request would serve page 1.
     *
     * Registered in boot(): PaginationServiceProvider installs the framework
     * default in register(), so this override runs afterwards and wins.
     */
    protected function resolveJsonApiPageNumber(): void
    {
        $app = $this->app;

        Paginator::currentPageResolver(function ($pageName = 'page') use ($app) {
            $page = $app['request']->input($pageName);

            if (is_array($page)) {
                $page = $page['number'] ?? null;
            }

            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int) $page >= 1) {
                return (int) $page;
            }

            return 1;
        });
    }
}
