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
            'nome' => 'nullable|required_unless:destino,L|string|max:100',
            'telefone' => 'nullable|string|max:50',
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
            'nome.required_unless' => 'Informe o nome de quem vai retirar ou receber.',
            'endereco.required_if' => 'Informe o endereço da entrega.',
            'itens.required' => 'Inclua pelo menos um produto.',
            'itens.min' => 'Inclua pelo menos um produto.',
            'itens.*.quantidade.gt' => 'A quantidade tem que ser maior que zero.',
        ];
    }
}
