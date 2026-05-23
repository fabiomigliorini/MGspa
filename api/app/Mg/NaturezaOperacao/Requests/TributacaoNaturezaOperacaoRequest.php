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
            'icmscst' => 'nullable|numeric',
            'icmsbase' => 'nullable|numeric|min:0|max:100',
            'icmspercentual' => 'nullable|numeric|min:0|max:100',
            'icmslpbase' => 'nullable|numeric|min:0|max:100',
            'icmslppercentual' => 'nullable|numeric|min:0|max:100',
            'icmslppercentualimportado' => 'nullable|numeric|min:0|max:100',
            'csosn' => 'nullable|integer',
            'ipicst' => 'nullable|numeric',
            'piscst' => 'nullable|numeric',
            'pispercentual' => 'nullable|numeric|min:0|max:100',
            'cofinscst' => 'nullable|numeric',
            'cofinspercentual' => 'nullable|numeric|min:0|max:100',
            'csllpercentual' => 'nullable|numeric|min:0|max:100',
            'irpjpercentual' => 'nullable|numeric|min:0|max:100',
            'funruralpercentual' => 'nullable|numeric|min:0|max:100',
            'senarpercentual' => 'nullable|numeric|min:0|max:100',
            'fethabkg' => 'nullable|numeric|min:0',
            'iagrokg' => 'nullable|numeric|min:0',
            'certidaosefazmt' => 'nullable|boolean',
            'acumuladordominiovista' => 'nullable|integer',
            'acumuladordominioprazo' => 'nullable|integer',
            'historicodominio' => 'nullable|string|max:255',
            'movimentacaofisica' => 'nullable|boolean',
            'movimentacaocontabil' => 'nullable|boolean',
            'observacoesnf' => 'nullable|string',
        ];
    }
}
