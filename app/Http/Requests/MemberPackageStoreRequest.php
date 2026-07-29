<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberPackageStoreRequest extends FormRequest
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
            'package_name'          => 'required|string',            
            'branch_store_id'       => 'required',
            'days'                  => 'required|numeric',
            'package_type_id'       => 'exists:member_package_types,id',
            'package_category_id'   => 'exists:member_package_categories,id',
            'package_price'         => 'required|numeric',
            'admin_price'           => 'required|numeric',
            'description'           => '',
            'is_all_club'           => 'required',
            'is_installment_plan'   => ['required', 'boolean'],
            'installment_monthly_amount' => [Rule::requiredIf(fn () => $this->boolean('is_installment_plan')), 'nullable', 'numeric', 'min:1'],
        ];
    }

    protected function prepareForValidation()
    {
        $isInstallmentPlan = filter_var(
            $this->input('is_installment_plan', false),
            FILTER_VALIDATE_BOOLEAN
        );

        $this->merge([
            'package_price' => str_replace(',', '', $this->package_price),
            'admin_price' => str_replace(',', '', $this->admin_price),
            'installment_monthly_amount' => $isInstallmentPlan
                ? str_replace(',', '', (string) $this->installment_monthly_amount)
                : null,
        ]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->boolean('is_installment_plan')) return;
            $branch = \App\Models\BranchStore::find($this->branch_store_id);
            if (!$branch || !$branch->member_installment_enabled) {
                $validator->errors()->add('is_installment_plan', 'Cicilan membership belum diaktifkan untuk cabang ini.');
            }
            if ((int) $this->days < 360) {
                $validator->errors()->add('days', 'Paket cicilan 12 bulan harus minimal 360 hari.');
            }
            if ((int) $this->package_price !== (int) $this->installment_monthly_amount * 12) {
                $validator->errors()->add('package_price', 'Harga paket harus sama dengan cicilan bulanan × 12.');
            }
        });
    }
}
