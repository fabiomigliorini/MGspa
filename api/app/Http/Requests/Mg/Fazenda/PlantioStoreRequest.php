<?php

namespace App\Http\Requests\Mg\Fazenda;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mg\Safra\Safra;

class PlantioStoreRequest extends FormRequest
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
        $unico = Rule::unique('tblplantio', 'talhao')->where(
            fn ($q) => $q->where('codsafra', $this->route('codsafra'))
                ->where('codfazenda', $this->input('codfazenda'))
                ->where('codvariedade', $this->input('codvariedade'))
                ->whereNull('inativo')
        );

        // Data do plantio limitada ao periodo da safra: do inicio do ano de
        // plantio ao fim do ano de colheita (colheita cai no anoplantio quando
        // safra de ciclo unico).
        $safra = Safra::findOrFail($this->route('codsafra'));
        $dataMin = $safra->anoplantio . '-01-01';
        $dataMax = ($safra->anocolheita ?: $safra->anoplantio) . '-12-31';

        return [
            'codsafra' => ['required', 'exists:tblsafra,codsafra'],
            'codfazenda' => ['required', 'exists:tblfazenda,codfazenda'],
            'talhao' => ['required', 'string', 'max:60', $unico],
            'codvariedade' => ['required', 'exists:tblvariedade,codvariedade'],
            'dataplantio' => ['required', 'date', "after_or_equal:$dataMin", "before_or_equal:$dataMax"],
            'areaplantada' => ['required', 'numeric', 'gt:0'],
            'expectativasacas' => ['nullable', 'numeric', 'gte:0'],
            'geometria' => ['nullable', 'array'],
            'cor' => ['nullable', 'string', 'max:9'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'codtalhao' => ['nullable', 'exists:tbltalhao,codtalhao'],
        ];
    }

    public function messages()
    {
        return [
            'dataplantio.after_or_equal' => 'A data do plantio nao pode ser anterior ao inicio da safra.',
            'dataplantio.before_or_equal' => 'A data do plantio nao pode ser posterior ao fim da safra.',
        ];
    }
}
