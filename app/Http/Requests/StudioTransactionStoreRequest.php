<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudioTransactionStoreRequest extends FormRequest
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
            'name'              => 'required',
            'booking_date'      => 'required',
            'phone_number'      => 'required',
            'studio_name'       => 'required',
            'package_id'        => 'required|exists:studio_packages,id',
            'role'              => 'required',
            'staff_name'        => 'required',
            'payment_status'    => 'required',
        ];
    }
}
