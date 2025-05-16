<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AppoitmentStoreRequest extends FormRequest
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
            'date_time'             => 'required',
            'full_name'             => 'required',
            'appointment_date'      => 'required',
            'appointment_code'      => '',
            'phone_number'          => 'required',
            'email'                 => '',
            'source'                => '',
            'description'           => '',
            'status'                => '',
            'fc_id'                 => 'required|exists:fitness_consultants,id',
            'cs_id'                 => 'required|exists:customer_services,id',
        ];
    }
}
