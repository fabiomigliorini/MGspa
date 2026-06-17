<?php

namespace App\Http\Requests\Mg\Safra;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mg\Safra\CargaColheitaService;

class CargaColheitaSincronizarRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'uuid' => ['required', 'string'],
            'codsafra' => ['required', 'exists:tblsafra,codsafra'],
            'etapa' => ['required', Rule::in(CargaColheitaService::ETAPAS)],
            'data' => ['required', 'date'],
            'plantios' => ['array'],
            // nullable: o service (sincronizarPlantios) ignora entradas sem
            // codplantio; nao rejeitar a carga inteira por uma linha vazia.
            'plantios.*.codplantio' => ['nullable', 'exists:tblplantio,codplantio'],
            'plantios.*.percentual' => ['nullable', 'numeric', 'gte:0'],
            'pesobruto' => ['nullable', 'numeric', 'gte:0'],
            'tara' => ['nullable', 'numeric', 'gte:0'],
            'codveiculo' => ['nullable', 'exists:tblveiculo,codveiculo'],
            'codpessoamotorista' => ['nullable', 'exists:tblpessoa,codpessoa'],
        ];
    }
}
