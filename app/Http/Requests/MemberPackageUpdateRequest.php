<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MemberPackageUpdateRequest extends FormRequest
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
            'package_name'          => 'string',
            'days'                  => 'numeric',
            'package_type_id'       => 'exists:member_package_types,id',
            'package_category_id'   => 'exists:member_package_categories,id',
            'package_price'         => 'numeric',
            'admin_price'           => 'numeric',
            'description'           => 'nullable',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'package_price' => str_replace(',', '', $this->package_price),
            'admin_price' => str_replace(',', '', $this->admin_price),
        ]);
    }
}
