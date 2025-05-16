<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadStoreRequest extends FormRequest
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
            'guest_code'            => '',
            'phone_number'          => 'required',
            'email'                 => '',
            'address'               => '',
            'source'                => '',
            'fc_id'                 => 'required|exists:fitness_consultants,id',
            'cs_id'                 => 'required|exists:customer_services,id',
        ];
    }
}
