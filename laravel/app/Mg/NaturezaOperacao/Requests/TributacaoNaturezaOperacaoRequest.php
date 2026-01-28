<?php

namespace Mg\NaturezaOperacao\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TributacaoNaturezaOperacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codtributacao' => 'required|integer|exists:tbltributacao,codtributacao',
            'codcfop' => 'required|integer|exists:tblcfop,codcfop',
            'codestado' => 'nullable|integer|exists:tblestado,codestado',
            'codtipoproduto' => 'nullable|integer|exists:tbltipoproduto,codtipoproduto',
            'ncm' => 'nullable|string|max:10',
            'bit' => 'nullable|boolean',

            // ICMS
            'icmscst' => 'nullable|numeric',
            'icmsbase' => 'nullable|numeric|min:0|max:100',
            'icmspercentual' => 'nullable|numeric|min:0|max:100',

            // ICMS Lucro Presumido
            'icmslpbase' => 'nullable|numeric|min:0|max:100',
            'icmslppercentual' => 'nullable|numeric|min:0|max:100',
            'icmslppercentualimportado' => 'nullable|numeric|min:0|max:100',

            // Simples Nacional
            'csosn' => 'nullable|integer',

            // IPI
            'ipicst' => 'nullable|numeric',

            // PIS
            'piscst' => 'nullable|numeric',
            'pispercentual' => 'nullable|numeric|min:0|max:100',

            // COFINS
            'cofinscst' => 'nullable|numeric',
            'cofinspercentual' => 'nullable|numeric|min:0|max:100',

            // Outros tributos
            'csllpercentual' => 'nullable|numeric|min:0|max:100',
            'irpjpercentual' => 'nullable|numeric|min:0|max:100',
            'funruralpercentual' => 'nullable|numeric|min:0|max:100',
            'senarpercentual' => 'nullable|numeric|min:0|max:100',

            // Taxas específicas MT
            'fethabkg' => 'nullable|numeric|min:0',
            'iagrokg' => 'nullable|numeric|min:0',
            'certidaosefazmt' => 'nullable|boolean',

            // Domínio contábil
            'acumuladordominiovista' => 'nullable|integer',
            'acumuladordominioprazo' => 'nullable|integer',
            'historicodominio' => 'nullable|string|max:255',

            // Movimentação
            'movimentacaofisica' => 'nullable|boolean',
            'movimentacaocontabil' => 'nullable|boolean',

            // Observações
            'observacoesnf' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'codtributacao.required' => 'A tributação é obrigatória!',
            'codtributacao.exists' => 'A tributação informada não existe!',
            'codcfop.required' => 'O CFOP é obrigatório!',
            'codcfop.exists' => 'O CFOP informado não existe!',
            'codestado.exists' => 'O estado informado não existe!',
            'codtipoproduto.exists' => 'O tipo de produto informado não existe!',
            'ncm.max' => 'O NCM deve ter no máximo 10 caracteres!',
        ];
    }
}
