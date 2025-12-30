<?php

namespace Mg\NotaFiscal\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotaFiscalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codfilial' => 'required|integer|exists:tblfilial,codfilial',
            'codestoquelocal' => 'nullable|integer|exists:tblestoquelocal,codestoquelocal',
            'codpessoa' => 'required|integer|exists:tblpessoa,codpessoa',
            'codnaturezaoperacao' => 'required|integer|exists:tblnaturezaoperacao,codnaturezaoperacao',
            'codoperacao' => 'required|integer|exists:tbloperacao,codoperacao',

            'emitida' => 'required|boolean',
            'modelo' => 'required|string|max:10',
            'serie' => 'required|string|max:10',
            'numero' => 'nullable|integer',
            'nfechave' => 'nullable|string|max:44',

            'emissao' => 'required|date',
            'saida' => 'required|date',

            // Valores
            'valordesconto' => 'nullable|numeric|min:0',
            'valorfrete' => 'nullable|numeric|min:0',
            'valorseguro' => 'nullable|numeric|min:0',
            'valoroutras' => 'nullable|numeric|min:0',

            // Informações adicionais
            'informacoescontribuinte' => 'nullable|string',
            'informacoesfisco' => 'nullable|string',

            // Transporte
            'frete' => 'nullable|integer|in:0,1,2,3,4,9',
            'codtransportador' => 'nullable|integer|exists:tblpessoa,codpessoa',
            'volume' => 'nullable|integer',
            'pesobruto' => 'nullable|numeric|min:0',
            'pesoliquido' => 'nullable|numeric|min:0',
            'placa' => 'nullable|string|max:10',
            'codestadoplaca' => 'nullable|integer|exists:tblestado,codestado',

            // Campos de controle NFe
            'nfeautorizacao' => 'nullable|string|max:100',
            'nfecancelamento' => 'nullable|string|max:100',
            'nfeinutilizacao' => 'nullable|string|max:100',
            'nfexml' => 'nullable|string',
            'nfeprotocolo' => 'nullable|string|max:100',
            'nferejeicao' => 'nullable|string',
        ];
    }
}
