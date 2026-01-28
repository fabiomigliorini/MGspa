<?php

namespace Mg\NaturezaOperacao\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NaturezaOperacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'naturezaoperacao' => 'required|string|max:50',
            'codoperacao' => 'required|integer|exists:tbloperacao,codoperacao',
            'emitida' => 'nullable|boolean',
            'observacoesnf' => 'nullable|string',
            'mensagemprocom' => 'nullable|string',
            'codnaturezaoperacaodevolucao' => 'nullable|integer|exists:tblnaturezaoperacao,codnaturezaoperacao',
            'codtipotitulo' => 'required|integer|exists:tbltipotitulo,codtipotitulo',
            'codcontacontabil' => 'nullable|integer|exists:tblcontacontabil,codcontacontabil',
            'finnfe' => 'nullable|integer|in:1,2,3,4',
            'ibpt' => 'nullable|boolean',
            'codestoquemovimentotipo' => 'nullable|integer|exists:tblestoquemovimentotipo,codestoquemovimentotipo',
            'estoque' => 'nullable|boolean',
            'financeiro' => 'nullable|boolean',
            'compra' => 'nullable|boolean',
            'venda' => 'nullable|boolean',
            'vendadevolucao' => 'nullable|boolean',
            'transferencia' => 'nullable|boolean',
            'preco' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'naturezaoperacao.required' => 'A natureza da operação é obrigatória!',
            'naturezaoperacao.max' => 'A natureza da operação deve ter no máximo 50 caracteres!',
            'codoperacao.required' => 'A operação é obrigatória!',
            'codoperacao.exists' => 'A operação selecionada não existe!',
            'codnaturezaoperacaodevolucao.exists' => 'A natureza de operação de devolução selecionada não existe!',
            'codtipotitulo.required' => 'O tipo de título é obrigatório!',
            'codtipotitulo.exists' => 'O tipo de título selecionado não existe!',
            'codcontacontabil.exists' => 'A conta contábil selecionada não existe!',
            'finnfe.in' => 'A finalidade NFe deve ser 1 (Normal), 2 (Complementar), 3 (Ajuste) ou 4 (Devolução/Retorno)!',
            'codestoquemovimentotipo.exists' => 'O tipo de movimento de estoque selecionado não existe!',
        ];
    }
}
