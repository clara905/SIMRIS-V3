<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'catatan' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
