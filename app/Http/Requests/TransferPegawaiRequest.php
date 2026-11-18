<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferPegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_tujuan_id' => [
                'required',
                'integer',
                'exists:unit_organisasi,id',
            ],
            'tanggal_efektif' => [
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
