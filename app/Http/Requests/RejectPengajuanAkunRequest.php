<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectPengajuanAkunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'catatan' => [
                'required',
                'string',
                'min:3',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'catatan.required' => 'Alasan penolakan wajib diisi.',
            'catatan.min' => 'Alasan penolakan minimal 3 karakter.',
            'catatan.max' => 'Alasan penolakan maksimal 2000 karakter.',
        ];
    }
}
