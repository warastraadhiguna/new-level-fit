<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassDetailRequest extends FormRequest
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
            'class_schedule_id' => [
                Rule::requiredIf($this->isMethod('post')),
                'exists:class_schedules,id',
            ],
            'user_id'               => 'nullable|integer',      
            'member_id'             => 'nullable|integer',            
            'name'                  => 'required',
            'phone'                 => 'required',     
            'email'                 => 'sometimes',   
            'status'                => 'sometimes',      
        ];
    }
}