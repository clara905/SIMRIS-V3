<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeactivatePegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal_selesai' => [
                'required',
                'date',
            ],
            'catatan' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
