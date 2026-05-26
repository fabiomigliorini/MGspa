<?php

namespace Mg\Titulo;

use Illuminate\Foundation\Http\FormRequest;

class LiquidacaoTituloStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codpessoa'             => 'required|integer|exists:tblpessoa,codpessoa',
            'codportador'           => 'required|integer|exists:tblportador,codportador',
            'transacao'             => 'required|date',
            'observacao'            => 'nullable|string|max:200',
            'titulos'               => 'required|array|min:1',
            'titulos.*.codtitulo'   => 'required|integer|exists:tbltitulo,codtitulo',
            'titulos.*.saldo'       => 'required|numeric|min:0',
            'titulos.*.multa'       => 'nullable|numeric|min:0',
            'titulos.*.juros'       => 'nullable|numeric|min:0',
            'titulos.*.desconto'    => 'nullable|numeric|min:0',
            'titulos.*.total'       => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'titulos.required' => 'Selecione ao menos um título!',
            'titulos.min'      => 'Selecione ao menos um título!',
            'required'         => 'Campo obrigatório',
        ];
    }
}
