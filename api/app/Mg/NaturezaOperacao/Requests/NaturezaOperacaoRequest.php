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
            'preco' => 'nullable|integer|in:1,2,3,4',
        ];
    }
}
