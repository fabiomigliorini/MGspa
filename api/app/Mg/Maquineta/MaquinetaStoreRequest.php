<?php

namespace Mg\Maquineta;

use Illuminate\Foundation\Http\FormRequest;

// Manual (integracao vazia) ou PagarMe (P, cria o POS junto). Saurus nasce do pareamento.
class MaquinetaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'integracao' => 'nullable|in:P',
            'apelido' => 'required|string|max:50',
            'serial' => 'nullable|required_if:integracao,P|string|max:50',
            'codfilial' => 'required|integer|exists:tblfilial,codfilial',
            'compartilhada' => 'boolean',
            'codpessoa' => 'nullable|required_without:integracao|integer|exists:tblpessoa,codpessoa',
        ];
    }
}
