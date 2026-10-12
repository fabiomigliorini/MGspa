<?php

namespace Mg\Titulo;

use Illuminate\Foundation\Http\FormRequest;

class TipoTituloUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipotitulo' => 'required|string|max:50',
            'observacoes' => 'nullable|string|max:255',
            'natureza' => 'required|in:R,P',
            'movimentaportador' => 'boolean',
            'pagar' => 'boolean',
            'receber' => 'boolean',
        ];
    }
}
