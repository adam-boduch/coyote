<?php

namespace Coyote\Http\Requests;

use Coyote\Repositories\Contracts\TagRepositoryInterface;
use Coyote\Rules\TagDeleted;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MicroblogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'parent_id'     => ['nullable', 'integer', Rule::exists('microblogs', 'id')->whereNull('deleted_at')],
            'text'          => 'required|string|max:12000',
            'tags'          => 'array|max:5',
            'tags.*.name'   => [
                'bail',
                'max:25',
                'tag',
                new TagDeleted($this->container[TagRepositoryInterface::class]),
                'tag_creation:300'
            ]
        ];
    }
}
