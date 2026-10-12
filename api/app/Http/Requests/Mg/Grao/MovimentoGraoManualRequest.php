<?php

namespace App\Http\Requests\Mg\Grao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ajuste MANUAL (comercial) no extrato. Quantidades POSITIVAS: o sinal vem do
 * papel (MovimentoGraoService::lancarManual). Basta bruto ou liquido.
 */
class MovimentoGraoManualRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'data' => ['nullable', 'date'],
            'codsafra' => ['nullable', 'exists:tblsafra,codsafra'],
            'papel' => ['required', Rule::in(['ORIGEM', 'DESTINO'])],
            'contatipo' => ['required', Rule::in(['PLANTIO', 'UNIDADE', 'CONTRATO'])],
            'codplantio' => ['nullable', 'required_if:contatipo,PLANTIO', 'exists:tblplantio,codplantio'],
            'codunidadearmazenadora' => [
                'nullable',
                'required_if:contatipo,UNIDADE',
                'exists:tblunidadearmazenadora,codunidadearmazenadora',
            ],
            'codcontrato' => ['nullable', 'required_if:contatipo,CONTRATO', 'exists:tblcontrato,codcontrato'],
            'bruto' => ['nullable', 'required_without:liquido', 'numeric', 'gte:0'],
            'desconto' => ['nullable', 'numeric', 'gte:0', 'lte:bruto'],
            'liquido' => ['nullable', 'numeric', 'gte:0'],
            'observacao' => ['nullable', 'string', 'max:255'],
        ];
    }
}
