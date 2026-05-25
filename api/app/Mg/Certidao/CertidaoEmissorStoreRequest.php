<?php

namespace Mg\Certidao;

use Illuminate\Foundation\Http\FormRequest;

class CertidaoEmissorStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'certidaoemissor' => 'required|string|max:30',
        ];
    }
}
