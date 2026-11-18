<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nip' => [
                'required',
                'string',
                'max:50',
                'unique:pegawai,nip',
            ],
            'nama' => [
                'required',
                'string',
                'max:255',
            ],
            'email_kontak' => [
                'nullable',
                'email',
                'max:255',
            ],
            'jenis_kepegawaian' => [
                'required',
                Rule::in([
                    'PNS',
                    'PPPK',
                    'NON_PNS',
                    'TNI',
                ]),
            ],
            'unit_id' => [
                'required',
                'integer',
                'exists:unit_organisasi,id',
            ],
            'tanggal_mulai' => [
                'required',
                'date',
            ],
        ];
    }
}
