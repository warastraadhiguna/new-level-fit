<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MemberStoreRequest extends FormRequest
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
            'full_name'             => 'required',
            'gender'                => 'required',
            'member_code'           => 'required|unique:members,member_code',
            'phone_number'          => 'required',
            'source_code_id'        => 'required|exists:source_codes,id',
            'member_package_id'     => 'required|exists:member_packages,id',
            'method_payment_id'     => 'required|exists:method_payments,id',
            'sold_by_id'            => 'required|exists:solds,id',
            'refferal_id'           => 'required|exists:refferals,id',
            'status'                => 'required',
            'description'           => '',
            'photos'                => 'mimes:png,jpg,jpeg'
        ];
    }
}
