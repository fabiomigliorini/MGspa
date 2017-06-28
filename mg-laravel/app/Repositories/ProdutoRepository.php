<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Produto;

/**
 * Description of ProdutoRepository
 *
 */
class ProdutoRepository extends MGRepositoryStatic
{
    public static $modelClass = 'App\\Models\\Produto';

    public static function validate($model = null, &$errors = null, $throwsException = true)
    {
        $data = $model->getAttributes();

        Validator::extend('nomeMarca', function ($attribute, $value, $parameters) {
            if (empty($parameters[0])) {
                return false;
            }
            //$marca = new MarcaRepository();
            $marca = MarcaRepository::findOrFail($parameters[0]);
            if (!empty($value) && !empty($parameters[0])) {
                if (strpos(strtoupper($value), strtoupper($marca->marca)) === false) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        });

        Validator::extend('tributacao', function ($attribute, $value, $parameters) {
            $ncm = NcmRepository::findOrFail($parameters[0]);
            $regs = $ncm->regulamentoIcmsStMtsDisponiveis();
            if (sizeof($regs) > 0) {
                if ($value != Tributacao::SUBSTITUICAO) {
                    return false;
                }
            }
            return true;
        });

        Validator::extend('tributacaoSubstituicao', function ($attribute, $value, $parameters) {
            $ncm = NcmRepository::findOrFail($parameters[0]);
            $regs = $ncm->regulamentoIcmsStMtsDisponiveis();
            if (empty($regs)) {
                if ($value == Tributacao::SUBSTITUICAO) {
                    return false;
                }
            }
            return true;
        });

        Validator::extend('ncm', function ($attribute, $value, $parameters) {
            $ncm = NcmRepository::findOrFail($value);
            if (strlen($ncm->ncm) == 8) {
                return true;
            } else {
                return false;
            }
        });

        $rules = [
            'produto' => [
                'max:100',
                'min:10',
                'required',
                Rule::unique('tblproduto')->ignore($data['id']??null, 'codproduto'),
                'nomeMarca:'.($data['codmarca']??null),
            ],
            'referencia' => [
                'max:50',
                'nullable',
            ],
            'codunidademedida' => [
                'numeric',
                'required',
            ],
            'codsubgrupoproduto' => [
                'numeric',
                'required',
            ],
            'codmarca' => [
                'numeric',
                'required',
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
                'required',
                //'tributacao:'.($data['codncm']??null),
                //'tributacaoSubstituicao:'.($data['codncm']??null),
            ],
            'codtipoproduto' => [
                'numeric',
                'required',
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

        $messages = [
            'produto.max' => 'O campo "produto" não pode conter mais que 100 caracteres!',
            'produto.min' => 'O campo "produto" não pode conter menos que 9 caracteres!',
            'produto.required' => 'O campo "produto" deve ser preenchido!',
            'produto.nome_marca' => 'Não contem o nome da marca no campo "produto"!',
            'produto.unique' => 'Já existe um produto com esta descrição!',

            'referencia.max' => 'O campo "referencia" não pode conter mais que 50 caracteres!',
            'codunidademedida.numeric' => 'O campo "codunidademedida" deve ser um número!',
            'codunidademedida.required' => 'O campo "codunidademedida" deve ser preenchido!',
            'codsubgrupoproduto.numeric' => 'O campo "codsubgrupoproduto" deve ser um número!',
            'codsubgrupoproduto.required' => 'O campo "codsubgrupoproduto" deve ser preenchido!',
            'codmarca.numeric' => 'O campo "codmarca" deve ser um número!',
            'codmarca.required' => 'O campo "codmarca" deve ser preenchido!',
            'preco.numeric' => 'O campo "preco" deve ser um número!',
            'importado.boolean' => 'O campo "importado" deve ser um verdadeiro/falso (booleano)!',
            'importado.required' => 'O campo "importado" deve ser preenchido!',
            'codtributacao.numeric' => 'O campo "codtributacao" deve ser um número!',
            'codtributacao.required' => 'O campo "codtributacao" deve ser preenchido!',
            'codtributacao.tributacao' => 'Existe Regulamento de ICMS ST para este NCM!',
            'codtributacao.tributacao_substituicao' => 'Não existe regulamento de ICMS ST para este NCM!',

            'codtipoproduto.numeric' => 'O campo "codtipoproduto" deve ser um número!',
            'codtipoproduto.required' => 'O campo "codtipoproduto" deve ser preenchido!',
            'site.boolean' => 'O campo "site" deve ser um verdadeiro/falso (booleano)!',
            'site.required' => 'O campo "site" deve ser preenchido!',
            'descricaosite.max' => 'O campo "descricaosite" não pode conter mais que 1024 caracteres!',
            'codncm.numeric' => 'O campo "codncm" deve ser um número!',
            'codcest.numeric' => 'O campo "codcest" deve ser um número!',
            'observacoes.max' => 'O campo "observacoes" não pode conter mais que 255 caracteres!',
            'codopencart.numeric' => 'O campo "codopencart" deve ser um número!',
            'codopencartvariacao.numeric' => 'O campo "codopencartvariacao" deve ser um número!',

        ];


        $validator = Validator::make($data, $rules, $messages);

        if ($throwsException) {
            $validator->validate();
            return true;
        }

        if (!$validator->passes()) {
            $errors = $validator->errors();
            return false;
        }

        return true;
    }

    public static function details($model = null)
    {
        $details = $model->getAttributes();
        $details['Marca'] = [
            'codmarca' => $model->Marca->codmarca,
            'marca' => $model->Marca->marca,
        ];
        $details['SubGrupoProduto'] = $model->SubGrupoProduto->getAttributes();
        $details['GrupoProduto'] = $model->SubGrupoProduto->GrupoProduto->getAttributes();
        $details['FamiliaProduto'] = $model->SubGrupoProduto->GrupoProduto->FamiliaProduto->getAttributes();
        $details['SecaoProduto'] = $model->SubGrupoProduto->GrupoProduto->FamiliaProduto->SecaoProduto->getAttributes();
        if (!empty($model->codprodutoimagem)) {
            $details['Imagem'] = $model->ProdutoImagem->Imagem->getAttributes();
            $details['Imagem']['url'] = $model->ProdutoImagem->Imagem->url;
        }

        $variacoes = [];
        foreach ($model->ProdutoVariacaoS()->orderByRaw('variacao ASC NULLS FIRST')->get() as $pv) {
            $variacao = $pv->getAttributes();

            if (!empty($model->codprodutoimagem)) {
                $details['Imagem'] = $model->ProdutoImagem->Imagem->getAttributes();
                $details['Imagem']['url'] = $model->ProdutoImagem->Imagem->url;
            }
            $variacoes[] = $variacao;
        }
        $details['Variacoes'] = $variacoes;

        $barras = [];
        foreach ($model->ProdutoBarraS()->orderByRaw('barras ASC NULLS FIRST')->get() as $pb) {
            $barra = $pb->getAttributes();
            $barra['preco'] = $pb->precopronto;
            $barras[] = $barra;
        }
        $details['Barras'] = $barras;

        $embalagens = [];
        $embalagens[] = [
            'codprodutoembalagem' => null,
            'codunidademedida' => $model->codunidademedida,
            'quantidade' => 1,
            'preco' => $model->preco,
            'UnidadeMedida' => $model->UnidadeMedida->getAttributes(),
        ];
        foreach ($model->ProdutoEmbalagemS()->orderByRaw('quantidade ASC NULLS FIRST')->get() as $pe) {
            $embalagem = $pe->getAttributes();
            $embalagem['preco'] = $pe->precopronto;
            $embalagem['UnidadeMedida'] = $pe->UnidadeMedida->getAttributes();

            $embalagens[] = $embalagem;
        }
        $details['Embalagens'] = $embalagens;

        return $details;
    }

}
