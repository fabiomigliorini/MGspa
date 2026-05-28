<?php

namespace Mg\Titulo;

use Illuminate\Foundation\Http\FormRequest;

class TipoTituloStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipotitulo' => 'required|string|max:20',
            'observacoes' => 'nullable|string|max:255',
            'codtipomovimentotitulo' => 'nullable|integer|exists:tbltipomovimentotitulo,codtipomovimentotitulo',
            'pagar' => 'boolean',
            'receber' => 'boolean',
            'debito' => 'boolean',
            'credito' => 'boolean',
        ];
    }
}
