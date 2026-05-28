<?php

namespace Mg\Titulo;

use Illuminate\Foundation\Http\FormRequest;

class TipoMovimentoTituloUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipomovimentotitulo' => 'required|string|max:20',
            'observacao' => 'nullable|string|max:255',
            'implantacao' => 'boolean',
            'ajuste' => 'boolean',
            'armotizacao' => 'boolean',
            'juros' => 'boolean',
            'desconto' => 'boolean',
            'pagamento' => 'boolean',
            'estorno' => 'boolean',
        ];
    }
}
