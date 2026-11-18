<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePegawaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pegawai = $this->route('pegawai');

        return [
            'nip' => [
                'required',
                'string',
                'max:50',
                Rule::unique('pegawai', 'nip')
                    ->ignore($pegawai?->id),
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
        ];
    }
}
