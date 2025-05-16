<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrainerSessionUpdateRequest extends FormRequest
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
            'member_id'             => 'exists:members,id',
            'trainer_id'            => 'exists:personal_trainers,id',
            'start_date'            => 'nullable',
            'start_time2'           => 'nullable',
            'trainer_package_id'    => 'exists:trainer_packages,id',
            'user_id'               => '',
            'description'           => ''
        ];
    }
}
