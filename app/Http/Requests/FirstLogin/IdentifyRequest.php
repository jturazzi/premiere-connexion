<?php

namespace App\Http\Requests\FirstLogin;

use Illuminate\Foundation\Http\FormRequest;

class IdentifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'samaccountname' => ['required', 'string'],
        ];
    }
}
