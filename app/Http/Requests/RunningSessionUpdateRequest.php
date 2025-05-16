<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RunningSessionUpdateRequest extends FormRequest
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
            'member_id'             => 'exists:members,id',
            'trainer_package_id'    => 'exists:trainer_packages,id',
            'session_total'         => '',
            'check_in'              => '',
            'status'                => '',
            'personal_trainer_id'   => 'exists:personal_trainers,id',
            'customer_service_id'   => 'exists:customer_services,id',
        ];
    }
}
