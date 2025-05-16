<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunningSessionStoreRequest extends FormRequest
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
            'member_id'             => 'required|exists:members,id',
            'trainer_package_id'    => 'required|exists:trainer_packages,id',
            'session_total'         => 'required',
            'check_in'              => 'required',
            'status'                => 'required',
            'personal_trainer_id'   => 'required|exists:personal_trainers,id',
            'customer_service_id'   => 'required|exists:customer_services,id',
        ];
    }
}
