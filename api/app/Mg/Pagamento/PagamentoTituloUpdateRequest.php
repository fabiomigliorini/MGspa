<?php

namespace Mg\Pagamento;

use Illuminate\Foundation\Http\FormRequest;

class PagamentoTituloUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codpessoa'   => 'required|integer|exists:tblpessoa,codpessoa',
            'codportador' => 'nullable|integer|exists:tblportador,codportador',
            'meio'        => 'nullable|integer',
            'transacao'   => 'required|date',
            'observacao'  => 'nullable|string|max:300',
            // a data mudou (TASK-204): por que
            'justificativa' => 'nullable|string|max:300',
        ];
    }
}
