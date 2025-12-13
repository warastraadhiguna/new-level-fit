<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClassScheduleRequest extends FormRequest
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
            'class_instructor_id'   => 'required|exists:class_instructors,id',
            'name'                  => 'required',      
            'note'                  => 'required',            
            'price'                 => 'required|numeric',
            'capacity'              => 'required|numeric',     
            'real_capacity'         => 'sometimes',   
            'is_active'             => 'sometimes',         
        ];
    }
}