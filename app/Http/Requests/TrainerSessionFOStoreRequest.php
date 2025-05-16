<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrainerSessionFOStoreRequest extends FormRequest
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
            'active_period'         => 'required|string',
            'member_id'             => 'required|exists:members,id',
            'trainer_id'            => 'required|exists:trainers,id',
            'remaining_session'     => 'required',
            'status'                => 'required'
        ];
    }
}
