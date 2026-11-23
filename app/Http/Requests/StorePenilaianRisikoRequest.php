<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePenilaianRisikoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'matriks_id' => [
                'required',
                'integer',
                'exists:matriks_risiko,id',
            ],
            'jenis_penilaian' => [
                'required',
                'string',
                Rule::in(['INHEREN', 'RESIDUAL']),
            ],
            'alasan_kemungkinan' => [
                'required',
                'string',
            ],
            'alasan_dampak' => [
                'required',
                'string',
            ],
            'kesimpulan' => [
                'required',
                'string',
            ],
        ];
    }
}
