<?php

namespace App\Http\Requests\Produto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\Repositories\UsuarioRepository;
use App\Repositories\ProdutoRepository;

class ProdutoUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return UsuarioRepository::permitido($this->user(), 'Estoque', UsuarioRepository::PERMISSAO_MANUTENCAO);
    }

    public function rules()
    {
        $id = $this->route('produto');
        $rules = [
            'produto' => [
                'max:100',
                'min:10',
                Rule::unique('tblproduto')->ignore($id, 'codproduto'),
                //'nomeMarca:'.($data['codmarca']??null),
            ],
            'referencia' => [
                'max:50',
                'nullable',
            ],
            'codunidademedida' => [
                'numeric',
            ],
            'codsubgrupoproduto' => [
                'numeric',
            ],
            'codmarca' => [
                'numeric',
            ],
            'preco' => [
                'numeric',
                'nullable',
            ],
            'importado' => [
                'boolean',
            ],
            'codtributacao' => [
                'numeric',
                //'tributacao:'.($data['codncm']??null),
                //'tributacaoSubstituicao:'.($data['codncm']??null),
            ],
            'codtipoproduto' => [
                'numeric',
            ],
            'site' => [
                'boolean',
            ],
            'descricaosite' => [
                'max:1024',
                'nullable',
            ],
            'codncm' => [
                'numeric',
                'nullable',
                //'ncm'
            ],
            'codcest' => [
                'numeric',
                'nullable',
            ],
            'observacoes' => [
                'max:255',
                'nullable',
            ],
            'codopencart' => [
                'numeric',
                'nullable',
            ],
            'codopencartvariacao' => [
                'numeric',
                'nullable',
            ],
        ];
        return $rules;
        return ProdutoRepository::rules($this->all());
    }

    public function messages()
    {
        return ProdutoRepository::rulesMessages();
    }

}
