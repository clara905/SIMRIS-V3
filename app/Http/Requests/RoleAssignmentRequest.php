<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_code' => [
                'required',
                'string',
                Rule::exists('roles', 'kode'),
            ],
            'unit_id' => [
                'nullable',
                'integer',
                Rule::exists('unit_organisasi', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role_code.required' => 'Role wajib dipilih.',
            'role_code.exists' => 'Role tidak ditemukan.',
            'unit_id.integer' => 'Unit organisasi harus berupa ID angka.',
            'unit_id.exists' => 'Unit organisasi tidak ditemukan.',
        ];
    }
}
