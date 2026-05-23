<?php

namespace App\Http\Requests\Mg\Pessoa;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Mg\Pessoa\Pessoa;
use Mg\Pessoa\DependenteService;

class DependenteStoreRequest extends FormRequest
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
        return [
            'codpessoa' => [
                'required',
                'integer',
                'exists:tblpessoa,codpessoa',
                'different:codpessoaresponsavel',
                function ($attribute, $value, $fail) {
                    // Valida CPF se depirrf = true
                    if ($this->input('depirrf')) {
                        $pessoa = Pessoa::find($value);
                        if ($pessoa && empty($pessoa->cnpj)) {
                            $fail('Dependente com dedução de IRRF deve ter CPF cadastrado.');
                        }
                    }
                },
            ],
            'codpessoaresponsavel' => [
                'required',
                'integer',
                'exists:tblpessoa,codpessoa',
            ],
            'datainicio' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'datafim' => [
                'sometimes',
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    $datainicio = $this->input('datainicio');
                    if ($value && $datainicio && $value < $datainicio) {
                        $fail('A data fim deve ser igual ou posterior à data de início.');
                    }
                },
            ],
            'tipdep' => [
                'nullable',
                'string',
                Rule::in(array_keys(DependenteService::TIPDEP_LABELS)),
            ],
            'depirrf' => [
                'nullable',
                'boolean',
            ],
            'depplano' => [
                'nullable',
                'boolean',
            ],
            'depsfam' => [
                'nullable',
                'boolean',
                function ($attribute, $value, $fail) {
                    // Valida se tipo permite salário família
                    if ($value && $this->input('tipdep')) {
                        if (!in_array($this->input('tipdep'), DependenteService::TIPDEP_PERMITE_SALFAM)) {
                            $fail("O tipo de dependente '{$this->input('tipdep')}' não permite salário família.");
                        }
                    }
                },
            ],
            'guardajudicial' => [
                'nullable',
                'boolean',
            ],
            'incsocfam' => [
                'nullable',
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
                'nullable',
                'boolean',
            ],
            'pensaovalor' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    $pensaoalimenticia = $this->input('pensaoalimenticia');
                    $pensaopercentual = $this->input('pensaopercentual');

                    // Check if values are actually filled (greater than 0)
                    $hasValor = is_numeric($value) && $value > 0;
                    $hasPercentual = is_numeric($pensaopercentual) && $pensaopercentual > 0;

                    // Se tem pensão, precisa ter valor OU percentual
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
                'required_if:pensaoalimenticia,true',
            ],
            'pensaoagencia' => [
                'nullable',
                'string',
                'required_if:pensaoalimenticia,true',
            ],
            'pensaoconta' => [
                'nullable',
                'string',
                'required_if:pensaoalimenticia,true',
            ],
            'pensaocpfbeneficiario' => [
                'nullable',
                'string',
                'required_if:pensaoalimenticia,true',
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
            'codpessoa.required' => 'O dependente é obrigatório.',
            'codpessoa.exists' => 'Dependente não encontrado.',
            'codpessoa.different' => 'Uma pessoa não pode ser dependente de si mesma.',
            'codpessoaresponsavel.required' => 'O responsável é obrigatório.',
            'codpessoaresponsavel.exists' => 'Responsável não encontrado.',
            'datainicio.required' => 'A data de início é obrigatória.',
            'datafim.after_or_equal' => 'A data fim deve ser igual ou posterior à data de início.',
            'tipdep.in' => 'Tipo de dependente inválido.',
            'pensaovalor.required_if' => 'Pensão alimentícia requer valor fixo ou percentual.',
            'pensaovalor.required_without' => 'Deve informar o valor ou o percentual da pensão.',
            'pensaopercentual.required_if' => 'Pensão alimentícia requer valor fixo ou percentual.',
            'pensaopercentual.required_without' => 'Deve informar o valor ou o percentual da pensão.',
            'pensaopercentual.max' => 'O percentual da pensão não pode ser maior que 100%.',
            'pensaobanco.required_if' => 'Banco é obrigatório quando há pensão alimentícia.',
            'pensaoagencia.required_if' => 'Agência é obrigatória quando há pensão alimentícia.',
            'pensaoconta.required_if' => 'Conta é obrigatória quando há pensão alimentícia.',
            'pensaocpfbeneficiario.required_if' => 'CPF do beneficiário é obrigatório quando há pensão alimentícia.',
        ];
    }
}
