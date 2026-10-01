<?php

namespace Mg\Pagamento;

use Illuminate\Foundation\Http\FormRequest;

// Baixa de titulos pelo wizard de cobranca (contas e PDV): os titulos com
// capital, juros, multa e desconto, e uma forma por pagamento. O service
// confere portador, maquineta e cheque de cada forma.
class PagamentoTituloStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codpessoa'                       => 'required|integer|exists:tblpessoa,codpessoa',
            'transacao'                       => 'nullable|date',
            'observacao'                      => 'nullable|string|max:300',
            'titulos'                         => 'required|array|min:1',
            'titulos.*.codtitulo'             => 'required|integer|exists:tbltitulo,codtitulo',
            'titulos.*.saldo'                 => 'required|numeric|min:0',
            'titulos.*.multa'                 => 'nullable|numeric|min:0',
            'titulos.*.juros'                 => 'nullable|numeric|min:0',
            'titulos.*.desconto'              => 'nullable|numeric|min:0',
            'titulos.*.total'                 => 'required|numeric|min:0',
            'pagamentos'                      => 'nullable|array',
            'pagamentos.*.meio'               => 'nullable|integer',
            'pagamentos.*.total'              => 'required|numeric|min:0.01',
            'pagamentos.*.valortroco'         => 'nullable|numeric|min:0',
            'pagamentos.*.codpagamento'       => 'nullable|integer|exists:tblpagamento,codpagamento',
            'pagamentos.*.codpagamentoorigem' => 'nullable|integer|exists:tblpagamento,codpagamento',
            'pagamentos.*.codportador'        => 'nullable|integer|exists:tblportador,codportador',
            'pagamentos.*.codmaquineta'       => 'nullable|integer|exists:tblmaquineta,codmaquineta',
            'pagamentos.*.bandeira'           => 'nullable|integer',
            'pagamentos.*.autorizacao'        => 'nullable|string|max:20',
            'pagamentos.*.parcelas'           => 'nullable|integer|min:1',
            'pagamentos.*.cmc7'               => 'nullable|string|max:40',
            'pagamentos.*.chequevencimento'   => 'nullable|date',
            'pagamentos.*.chequecnpj'         => 'nullable|numeric',
            'pagamentos.*.chequeemitente'     => 'nullable|string|max:100',
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
