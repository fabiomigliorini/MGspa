<?php

namespace Mg\Pdv;

use Illuminate\Foundation\Http\FormRequest;

// Editar o dispositivo (TASK-46): cadastro (so' Administrador/Gerente manda) e configuracao.
// A filial nao vem: e' a do local de estoque.
// `pdv` e' o uuid de quem pede: o proprio dispositivo altera a configuracao dele.
class PdvUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pdv' => 'nullable|uuid',
            // cadastro
            'apelido' => 'nullable|string|max:100',
            'codsetor' => 'sometimes|required|integer|exists:tblsetor,codsetor',
            'codportador' => 'nullable|integer|exists:tblportador,codportador',
            'monitoramento' => 'nullable|date',
            'minutosesquecido' => 'nullable|integer',
            'observacoes' => 'nullable|string',
            // configuracao
            'codestoquelocal' => 'required|integer|exists:tblestoquelocal,codestoquelocal',
            'codnaturezaoperacao' => 'required|integer|exists:tblnaturezaoperacao,codnaturezaoperacao',
            'impressora' => 'nullable|string|max:100',
            'codmaquineta' => 'nullable|integer|exists:tblmaquineta,codmaquineta',
            'codportadorpix' => 'nullable|integer|exists:tblportador,codportador',
        ];
    }
}
