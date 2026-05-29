<?php

namespace Firevel\Api\Tests\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class StorePost extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string',
        ];
    }
}
