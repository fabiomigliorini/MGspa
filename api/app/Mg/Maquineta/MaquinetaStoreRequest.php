<?php

namespace Mg\Maquineta;

use Illuminate\Foundation\Http\FormRequest;

// Manual (integracao vazia), PagarMe (P, cria o POS junto) ou SafraPay (S, nasce sem PDV Saurus
// e se pareia dentro dela).
class MaquinetaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'integracao' => 'nullable|in:P,S',
            'apelido' => 'required|string|max:50',
            'serial' => 'nullable|required_if:integracao,P|string|max:50',
            'codfilial' => 'required|integer|exists:tblfilial,codfilial',
            'compartilhada' => 'boolean',
            'codpessoa' => 'nullable|required_without:integracao|integer|exists:tblpessoa,codpessoa',
        ];
    }
}
