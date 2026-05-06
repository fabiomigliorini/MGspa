<?php

namespace Mg\Titulo;

use Illuminate\Foundation\Http\FormRequest;

class TituloAgrupamentoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codpessoa'   => 'required|integer|exists:tblpessoa,codpessoa',
            'codfilial'   => 'required|integer|exists:tblfilial,codfilial',
            'codportador' => 'nullable|integer|exists:tblportador,codportador',
            'emissao'     => 'required|date',
            'observacao'  => 'nullable|string|max:200',
            'boleto'      => 'boolean',

            'titulos'             => 'required|array|min:1',
            'titulos.*.codtitulo' => 'required|integer|exists:tbltitulo,codtitulo',
            'titulos.*.saldo'     => 'required|numeric|min:0',
            'titulos.*.multa'     => 'nullable|numeric|min:0',
            'titulos.*.juros'     => 'nullable|numeric|min:0',
            'titulos.*.desconto'  => 'nullable|numeric|min:0',
            'titulos.*.total'     => 'required|numeric|min:0',

            'vencimentos'   => 'required|array|min:1',
            'vencimentos.*' => 'required|date',
            'valores'       => 'required|array|min:1',
            'valores.*'     => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'titulos.required' => 'Selecione ao menos um título!',
            'required'         => 'Campo obrigatório',
        ];
    }
}
