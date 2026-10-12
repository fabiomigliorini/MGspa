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

    public function messages(): array
    {
        return [
            'codestoquelocal.required' => 'Informe o local de estoque.',
            'codnaturezaoperacao.required' => 'Informe a natureza de operação.',
            'codsetor.required' => 'Informe o setor.',
            '*.exists' => 'Registro não encontrado: :attribute.',
            'apelido.max' => 'O apelido tem no máximo 100 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'codsetor' => 'setor',
            'codportador' => 'portador da gaveta de dinheiro',
            'codestoquelocal' => 'local de estoque',
            'codnaturezaoperacao' => 'natureza de operação',
            'codmaquineta' => 'maquineta padrão',
            'codportadorpix' => 'PIX padrão',
        ];
    }
}
