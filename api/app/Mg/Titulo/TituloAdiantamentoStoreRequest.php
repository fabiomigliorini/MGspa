<?php

namespace Mg\Titulo;

use Illuminate\Foundation\Http\FormRequest;

// Vale colaborador e adiantamentos, no contas e no PDV (M8 doc-3): o titulo e
// as formas do wizard, no formato da baixa de titulos. O service confere tipo,
// sentido, portador, maquineta e cheque de cada forma.
class TituloAdiantamentoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pdv'                             => 'nullable|uuid',
            'codfilial'                       => 'nullable|integer|exists:tblfilial,codfilial',
            'transacao'                       => 'nullable|date',
            'codtipotitulo'                   => 'required|integer|exists:tbltipotitulo,codtipotitulo',
            'codpessoa'                       => 'required|integer|exists:tblpessoa,codpessoa',
            'codcontacontabil'                => 'required|integer|exists:tblcontacontabil,codcontacontabil',
            'vencimento'                      => 'required|date',
            'observacao'                      => 'nullable|string|max:255',
            'pagamentos'                      => 'required|array|min:1',
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
            'pagamentos.required' => 'Informe como foi pago!',
            'required'            => 'Campo obrigatório',
        ];
    }
}
