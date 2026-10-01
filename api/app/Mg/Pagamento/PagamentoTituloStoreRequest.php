<?php

namespace Mg\Pagamento;

use Illuminate\Foundation\Http\FormRequest;

class PagamentoTituloStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // portador e' obrigatorio quando anda dinheiro (o service confere: o
    // encontro de contas sem dinheiro nao tem portador)
    public function rules(): array
    {
        return [
            'codpessoa'             => 'required|integer|exists:tblpessoa,codpessoa',
            'codportador'           => 'nullable|integer|exists:tblportador,codportador',
            'meio'                  => 'nullable|integer',
            'transacao'             => 'required|date',
            'observacao'            => 'nullable|string|max:300',
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
