<?php

namespace App\Http\Requests\Mg\Grao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mg\Grao\CargaService;

class CargaSincronizarRequest extends FormRequest
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
            'sentido' => ['required', Rule::in(CargaService::SENTIDOS)],
            'etapa' => ['required', Rule::in(CargaService::ETAPAS)],
            'data' => ['required', 'date'],
            'pbt' => ['nullable', 'numeric', 'gte:0'],
            'tara' => ['nullable', 'numeric', 'gte:0'],
            'umidade' => ['nullable', 'numeric'],
            'impureza' => ['nullable', 'numeric'],
            'avariados' => ['nullable', 'numeric'],
            'pontos' => ['array'],
            'pontos.*.papel' => ['required', Rule::in(['ORIGEM', 'DESTINO'])],
            'pontos.*.contatipo' => ['required', Rule::in(CargaService::CONTATIPOS)],
            'pontos.*.codplantio' => ['nullable', 'exists:tblplantio,codplantio'],
            'pontos.*.codunidadearmazenadora' => ['nullable', 'exists:tblunidadearmazenadora,codunidadearmazenadora'],
            'pontos.*.codcontrato' => ['nullable', 'exists:tblcontrato,codcontrato'],
            'pontos.*.liquido' => ['nullable', 'numeric', 'gte:0'],
        ];
    }
}
