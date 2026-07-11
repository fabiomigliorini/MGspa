<?php

namespace App\Http\Requests\Mg\Grao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ajuste MANUAL (comercial) no extrato. Os tres campos (bruto/desconto/liquido)
 * devem fechar a invariante liquido = bruto - desconto (tambem garantida no
 * service e por CHECK no banco).
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
            'bruto' => ['nullable', 'numeric'],
            'desconto' => ['nullable', 'numeric'],
            'liquido' => ['required', 'numeric'],
            'observacao' => ['nullable', 'string', 'max:255'],
        ];
    }
}
