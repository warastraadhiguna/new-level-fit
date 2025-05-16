<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClassUpdateRequest extends FormRequest
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
            'date_time'             => 'string',
            'class_name'            => '',
            'class_instructor_id'   => 'exists:class_instructors,id',
            'member_total'          => '',
            'class_price'           => 'numeric',
            'user_id'               => ''
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'class_price' => str_replace(',', '', $this->class_price)
        ]);
    }
}
