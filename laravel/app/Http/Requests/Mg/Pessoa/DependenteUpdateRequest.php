<?php

namespace App\Http\Requests\Mg\Pessoa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mg\Pessoa\Pessoa;
use Mg\Pessoa\Dependente;
use Mg\Pessoa\DependenteService;

class DependenteUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $dependente = $this->route('coddependente') ? Dependente::find($this->route('coddependente')) : null;

        return [
            'codpessoa' => [
                'sometimes',
                'integer',
                'exists:tblpessoa,codpessoa',
                function ($attribute, $value, $fail) use ($dependente) {
                    // Não pode ser igual ao responsável
                    $responsavel = $this->input('codpessoaresponsavel') ?? $dependente?->codpessoaresponsavel;
                    if ($value == $responsavel) {
                        $fail('Uma pessoa não pode ser dependente de si mesma.');
                    }

                    // Valida CPF se depirrf = true
                    $depirrf = $this->input('depirrf') ?? $dependente?->depirrf;
                    if ($depirrf) {
                        $pessoa = Pessoa::find($value);
                        if ($pessoa && empty($pessoa->cnpj)) {
                            $fail('Dependente com dedução de IRRF deve ter CPF cadastrado.');
                        }
                    }
                },
            ],
            'codpessoaresponsavel' => [
                'sometimes',
                'integer',
                'exists:tblpessoa,codpessoa',
                function ($attribute, $value, $fail) use ($dependente) {
                    $codpessoa = $this->input('codpessoa') ?? $dependente?->codpessoa;
                    if ($value == $codpessoa) {
                        $fail('Uma pessoa não pode ser dependente de si mesma.');
                    }
                },
            ],
            'datainicio' => [
                'sometimes',
                'date',
            ],
            'datafim' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($dependente) {
                    $datainicio = $this->input('datainicio') ?? $dependente?->datainicio;
                    if ($value && $datainicio && $value < $datainicio) {
                        $fail('A data fim deve ser igual ou posterior à data de início.');
                    }
                },
            ],
            'tipdep' => [
                'sometimes',
                'string',
                Rule::in(array_keys(DependenteService::TIPDEP_LABELS)),
            ],
            'depirrf' => [
                'sometimes',
                'boolean',
            ],
            'depplano' => [
                'sometimes',
                'boolean',
            ],
            'depsfam' => [
                'sometimes',
                'boolean',
                function ($attribute, $value, $fail) use ($dependente) {
                    // Valida se tipo permite salário família
                    if ($value) {
                        $tipdep = $this->input('tipdep') ?? $dependente?->tipdep;
                        if ($tipdep && !in_array($tipdep, DependenteService::TIPDEP_PERMITE_SALFAM)) {
                            $fail("O tipo de dependente '{$tipdep}' não permite salário família.");
                        }
                    }
                },
            ],
            'guardajudicial' => [
                'sometimes',
                'boolean',
            ],
            'incsocfam' => [
                'sometimes',
                'boolean',
            ],
            'motivofim' => [
                'nullable',
                'string',
            ],
            'observacao' => [
                'nullable',
                'string',
            ],
            'pensaoalimenticia' => [
                'sometimes',
                'boolean',
            ],
            'pensaovalor' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($dependente) {
                    $pensaoalimenticia = $this->has('pensaoalimenticia')
                        ? $this->input('pensaoalimenticia')
                        : $dependente?->pensaoalimenticia;

                    $pensaopercentual = $this->has('pensaopercentual')
                        ? $this->input('pensaopercentual')
                        : $dependente?->pensaopercentual;

                    // Check if values are actually filled (greater than 0)
                    $hasValor = is_numeric($value) && $value > 0;
                    $hasPercentual = is_numeric($pensaopercentual) && $pensaopercentual > 0;

                    // Se pensão ativa, precisa ter valor ou percentual
                    if ($pensaoalimenticia && !$hasValor && !$hasPercentual) {
                        $fail('Pensão alimentícia requer valor fixo ou percentual.');
                    }

                    // Valida mutuamente exclusivo
                    if ($hasValor && $hasPercentual) {
                        $fail('Pensão alimentícia não pode ter valor fixo e percentual ao mesmo tempo.');
                    }
                },
            ],
            'pensaopercentual' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'pensaobanco' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($dependente) {
                    $pensaoalimenticia = $this->input('pensaoalimenticia') ?? $dependente?->pensaoalimenticia;
                    if ($pensaoalimenticia && empty($value)) {
                        $fail('Banco é obrigatório quando há pensão alimentícia.');
                    }
                },
            ],
            'pensaoagencia' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($dependente) {
                    $pensaoalimenticia = $this->input('pensaoalimenticia') ?? $dependente?->pensaoalimenticia;
                    if ($pensaoalimenticia && empty($value)) {
                        $fail('Agência é obrigatória quando há pensão alimentícia.');
                    }
                },
            ],
            'pensaoconta' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($dependente) {
                    $pensaoalimenticia = $this->input('pensaoalimenticia') ?? $dependente?->pensaoalimenticia;
                    if ($pensaoalimenticia && empty($value)) {
                        $fail('Conta é obrigatória quando há pensão alimentícia.');
                    }
                },
            ],
            'pensaocpfbeneficiario' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($dependente) {
                    $pensaoalimenticia = $this->input('pensaoalimenticia') ?? $dependente?->pensaoalimenticia;
                    if ($pensaoalimenticia && empty($value)) {
                        $fail('CPF do beneficiário é obrigatório quando há pensão alimentícia.');
                    }
                },
            ],
            'pensaobeneficiario' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Get custom validation messages
     *
     * @return array
     */
    public function messages()
    {
        return [
            'codpessoa.exists' => 'Dependente não encontrado.',
            'codpessoaresponsavel.exists' => 'Responsável não encontrado.',
            'tipdep.in' => 'Tipo de dependente inválido.',
            'pensaopercentual.max' => 'O percentual da pensão não pode ser maior que 100%.',
        ];
    }
}
