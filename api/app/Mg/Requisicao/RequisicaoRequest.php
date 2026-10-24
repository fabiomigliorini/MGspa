<?php

namespace Mg\Requisicao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequisicaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codestoquelocalrequisitante' => 'nullable|integer|exists:tblestoquelocal,codestoquelocal',
            'codestoquelocalrequisitada' => 'required|integer|exists:tblestoquelocal,codestoquelocal',
            'destino' => ['required', Rule::in(array_keys(Requisicao::DESTINOS))],
            'codpessoa' => 'nullable|integer|exists:tblpessoa,codpessoa',
            'nomes' => 'nullable|required_unless:destino,L|array',
            'nomes.*' => 'required|string|max:100',
            // um telefone para cada nome; so' digitos, com o DDD: 10 (fixo) ou 11 (celular)
            'telefones' => ['nullable', 'required_with:nomes', 'array', 'size:' . count((array) $this->input('nomes'))],
            'telefones.*' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            'endereco' => 'nullable|required_if:destino,E|string|max:300',
            'codnegocio' => 'nullable|integer|exists:tblnegocio,codnegocio',
            'observacoes' => 'nullable|string|max:500',
            'itens' => 'required|array|min:1',
            'itens.*.codprodutobarra' => 'required|integer|exists:tblprodutobarra,codprodutobarra',
            'itens.*.quantidade' => 'required|numeric|gt:0',
        ];
    }

    public function messages(): array
    {
        return [
            'nomes.required_unless' => 'Informe quem vai retirar ou receber.',
            'nomes.*.required' => 'Informe o nome de quem vai retirar ou receber.',
            'telefones.required_with' => 'Informe um telefone para cada nome.',
            'telefones.size' => 'Informe um telefone para cada nome.',
            'telefones.*.required' => 'Informe um telefone para cada nome.',
            'telefones.*.regex' => 'Telefone inválido: informe o DDD e o número.',
            'endereco.required_if' => 'Informe o endereço da entrega.',
            'itens.required' => 'Inclua pelo menos um produto.',
            'itens.min' => 'Inclua pelo menos um produto.',
            'itens.*.quantidade.gt' => 'A quantidade tem que ser maior que zero.',
        ];
    }
}
