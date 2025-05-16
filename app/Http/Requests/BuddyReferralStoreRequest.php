<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuddyReferralStoreRequest extends FormRequest
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
            'referral_name'         => 'required',
            'buddy_referral_code'   => '',
            'full_name'             => 'required',
            'phone_number'          => 'required',
            'fc_id'                 => 'required|exists:fitness_consultants,id',
            'cs_id'                 => 'required|exists:customer_services,id',
            'email'                 => '',
            'description'           => '',
        ];
    }
}
