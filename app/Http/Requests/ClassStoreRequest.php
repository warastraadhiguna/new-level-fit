<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClassStoreRequest extends FormRequest
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
            'date_time'             => 'required|string',
            'class_name'            => 'required',
            'class_instructor_id'   => 'required|exists:class_instructors,id',
            'member_total'          => 'required',
            'class_price'           => 'required|numeric',
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
