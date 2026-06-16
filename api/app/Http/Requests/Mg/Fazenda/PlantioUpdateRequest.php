<?php

namespace App\Http\Requests\Mg\Fazenda;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlantioUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    // O codsafra vem sempre da URL (contexto), nunca do corpo da requisicao.
    protected function prepareForValidation()
    {
        $this->merge(['codsafra' => $this->route('codsafra')]);
    }

    public function rules()
    {
        // Talhao+variedade unico por safra+fazenda (entre ativos): permite o
        // mesmo talhao com 2 variedades (2 plantios), barra duplicata real.
        // Ignora o proprio registro (codplantio da URL) na edicao.
        $unico = Rule::unique('tblplantio', 'talhao')->where(
            fn ($q) => $q->where('codsafra', $this->route('codsafra'))
                ->where('codfazenda', $this->input('codfazenda'))
                ->where('codvariedade', $this->input('codvariedade'))
                ->whereNull('inativo')
        )->ignore($this->route('codplantio'), 'codplantio');

        return [
            'codsafra' => ['required', 'exists:tblsafra,codsafra'],
            'codfazenda' => ['required', 'exists:tblfazenda,codfazenda'],
            'talhao' => ['required', 'string', 'max:60', $unico],
            'codvariedade' => ['required', 'exists:tblvariedade,codvariedade'],
            'areaplantada' => ['required', 'numeric', 'gt:0'],
            'expectativasacas' => ['nullable', 'numeric', 'gte:0'],
            'geometria' => ['nullable', 'array'],
            'cor' => ['nullable', 'string', 'max:9'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'codtalhao' => ['nullable', 'exists:tbltalhao,codtalhao'],
        ];
    }
}
