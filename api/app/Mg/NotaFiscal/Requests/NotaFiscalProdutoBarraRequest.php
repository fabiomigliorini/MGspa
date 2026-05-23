<?php

namespace Mg\NotaFiscal\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotaFiscalProdutoBarraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codprodutobarra' => 'required|integer|exists:tblprodutobarra,codprodutobarra',
            'codnegocioprodutobarra' => 'nullable|integer|exists:tblnegocioprodutobarra,codnegocioprodutobarra',
            'codnotafiscalprodutobarraorigem' => 'nullable|integer|exists:tblnotafiscalprodutobarra,codnotafiscalprodutobarra',
            'codcfop' => 'required|integer|exists:tblcfop,codcfop',

            // Quantidade e Valores
            'ordem' => 'nullable|integer',
            'quantidade' => 'required|numeric|min:0.001',
            'valorunitario' => 'required|numeric|min:0',
            'valortotal' => 'required|numeric|min:0',
            'valordesconto' => 'nullable|numeric|min:0',
            'valorfrete' => 'nullable|numeric|min:0',
            'valorseguro' => 'nullable|numeric|min:0',
            'valoroutras' => 'nullable|numeric|min:0',

            // Tributos ICMS
            'csosn' => 'nullable|string|max:10',
            'icmscst' => 'nullable|numeric',
            'icmsbase' => 'nullable|numeric|min:0',
            'icmsbasepercentual' => 'nullable|numeric|min:0',
            'icmspercentual' => 'nullable|numeric|min:0',
            'icmsvalor' => 'nullable|numeric|min:0',
            'icmsstbase' => 'nullable|numeric|min:0',
            'icmsstpercentual' => 'nullable|numeric|min:0',
            'icmsstvalor' => 'nullable|numeric|min:0',

            // Tributos IPI
            'ipicst' => 'nullable|numeric',
            'ipibase' => 'nullable|numeric|min:0',
            'ipipercentual' => 'nullable|numeric|min:0',
            'ipivalor' => 'nullable|numeric|min:0',
            'ipidevolucaovalor' => 'nullable|numeric|min:0',

            // Tributos PIS
            'piscst' => 'nullable|numeric',
            'pisbase' => 'nullable|numeric|min:0',
            'pispercentual' => 'nullable|numeric|min:0',
            'pisvalor' => 'nullable|numeric|min:0',

            // Tributos COFINS
            'cofinscst' => 'nullable|numeric',
            'cofinsbase' => 'nullable|numeric|min:0',
            'cofinspercentual' => 'nullable|numeric|min:0',
            'cofinsvalor' => 'nullable|numeric|min:0',

            // Outros Tributos
            'irpjbase' => 'nullable|numeric|min:0',
            'irpjpercentual' => 'nullable|numeric|min:0',
            'irpjvalor' => 'nullable|numeric|min:0',
            'csllbase' => 'nullable|numeric|min:0',
            'csllpercentual' => 'nullable|numeric|min:0',
            'csllvalor' => 'nullable|numeric|min:0',
            'funruralpercentual' => 'nullable|numeric|min:0',
            'funruralvalor' => 'nullable|numeric|min:0',
            'senarpercentual' => 'nullable|numeric|min:0',
            'senarvalor' => 'nullable|numeric|min:0',
            'fethabkg' => 'nullable|numeric|min:0',
            'fethabvalor' => 'nullable|numeric|min:0',
            'iagrokg' => 'nullable|numeric|min:0',
            'iagrovalor' => 'nullable|numeric|min:0',

            // Outros
            'pedido' => 'nullable|string|max:100',
            'pedidoitem' => 'nullable|integer',
            'devolucaopercentual' => 'nullable|numeric|min:0',
            'descricaoalternativa' => 'nullable|string|max:500',
            'observacoes' => 'nullable|string',
            'certidaosefazmt' => 'nullable|boolean',
        ];
    }
}
