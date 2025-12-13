<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClassSessionRequest extends FormRequest
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
    protected function prepareForValidation(): void
    {
        $this->merge([
            'price' => str_replace(',', '', $this->price),
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'                  => 'required',
            'class_instructor_id'   => 'required|exists:class_instructors,id',
            'price'                 => 'required|numeric',
            'capacity'              => 'required|numeric',            
            'note'                  => 'required'
        ];
    }
}