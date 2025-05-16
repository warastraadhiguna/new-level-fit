<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrainerPackageUpdateRequest extends FormRequest
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
            'package_type_id'       => 'requiredexists:trainer_package_types,id',
            'number_of_session'     => 'required',
            'days'                  => 'required',
            'package_price'         => 'numeric',
            'admin_price'           => 'numeric',
            'description'           => 'nullable',
            'status'                => 'nullable',
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
