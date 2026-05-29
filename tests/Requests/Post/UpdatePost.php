<?php

namespace Firevel\Api\Tests\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePost extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'sometimes|string',
        ];
    }
}
