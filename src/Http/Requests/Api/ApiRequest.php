<?php

namespace Firevel\Api\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ApiRequest extends FormRequest
{
    /**
     * Default page size.
     */
    const DEFAULT_PAGE_SIZE = 20;

    /**
     * Get includes list.
     *
     * @return array
     */
    public function getIncludes()
    {
        if ($this->has('include')) {
            $include = explode(',', $this->input('include'));

            return array_map(function ($item) {
                return Str::camel($item);
            }, $include);
        }

        return [];
    }

    /**
     * Get sort options.
     *
     * @return array
     */
    public function getSort()
    {
        if ($this->has('sort')) {
            return explode(',', $this->input('sort'));
        }

        return [];
    }

    /**
     * Get pagination size.
     *
     * @return int|mixed
     */
    public function getPageSize()
    {
        if ($this->has('page.size')) {
            return $this->input('page.size');
        }

        return self::DEFAULT_PAGE_SIZE;
    }
}
