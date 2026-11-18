<?php

namespace Mg\Filial;

use Illuminate\Foundation\Http\FormRequest;

// Percentuais do Simples do mes (TASK-280). Mesmos limites da nota de servico,
// que recusaria o percentual puxado daqui.
class FilialSimplesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'competencia' => 'required|date_format:Y-m-d',
            'tributospercentual' => 'required|numeric|gt:0|max:99.99',
            'issaliquota' => 'required|numeric|gt:0|max:5',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // No Simples o ISS e' uma fatia do DAS: nunca passa do % total de tributos
            if (
                $this->filled('issaliquota')
                && $this->filled('tributospercentual')
                && (float) $this->input('issaliquota') > (float) $this->input('tributospercentual')
            ) {
                $validator->errors()->add('issaliquota', 'A alíquota do ISS não pode passar do % de Tributos do Simples.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'competencia.required' => 'Informe a competência.',
            'competencia.date_format' => 'Competência inválida.',
            'tributospercentual.required' => 'Informe o % de Tributos do Simples.',
            'tributospercentual.gt' => 'O % de Tributos tem que ser maior que zero.',
            'tributospercentual.max' => 'O % de Tributos aceita até 99,99%.',
            'issaliquota.required' => 'Informe a alíquota do ISS no Simples.',
            'issaliquota.gt' => 'A alíquota do ISS tem que ser maior que zero.',
            'issaliquota.max' => 'A alíquota do ISS não pode passar de 5%.',
        ];
    }
}
