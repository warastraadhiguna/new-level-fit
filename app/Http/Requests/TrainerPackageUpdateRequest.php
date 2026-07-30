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
            '_submission_token'     => 'required|string|size:36',
            'branch_store_id'       => 'required',            
            'package_name'          => 'string',
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
