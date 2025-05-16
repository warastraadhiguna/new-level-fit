<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrainerSessionFOUpdateRequest extends FormRequest
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
            'active_period'         => '',
            'member_id'             => 'exists:members,id',
            'trainer_id'            => 'exists:trainers,id',
            'remaining_session'     => 'required',
            'status'                => 'required'
        ];
    }
}
