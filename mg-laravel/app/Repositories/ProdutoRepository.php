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
    public static $modelClass = 'Produto';

    public static function validate($model = null, &$errors)
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

        if (!$validator->passes()) {
            $errors = $validator->errors()->all();
            return false;
        }

        return true;
    }


    public static function used($id = null)
    {
        if (!empty($id)) {
            $mdoel->findOrFail($id);
        }
        if ($model->ProdutoS->count() > 0) {
            return 'Produto sendo utilizada em Produtos!';
        }
        return false;
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

    public static function detailsById($id)
    {
        if (!$model = static::find($id)) {
            return false;
        }
        return static::details($model);
    }
}
/*

<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Produto;
use App\Models\Tributacao;
use App\Models\EstoqueLocal;

class ProdutoRepository extends MGRepository {

    public function boot() {
        $this->model = new Produto();
    }

    //put your code here
    public function validate($data = null, $id = null) {


    }

    public function validateSite($data, $id = null) {

        if (empty($id)) {
            $id = $this->model->codproduto;
        }
        $quantidade=[];
        foreach ($this->model->ProdutoEmbalagemS()->orderBy('quantidade')->get() as $pe) {
            $quantidade[$pe->codprodutoembalagem] = $pe->quantidade;
        }

        // Valida se peso é obrigatório
        Validator::extend('pesoObrigatorio', function ($attribute, $value, $parameters) use ($quantidade, $data) {
            foreach ($value as $codprodutoembalagem => $peso) {
                // se nao vende via site passa para proximo
                if (!($data['vendesite'][$codprodutoembalagem]??false)) {
                    continue;
                }
                // se peso da unidade nao esta preenchido
                if (empty($data['peso'][0])) {
                    return false;
                }
                // se peso da embalagem nao esta preenchido
                if (empty($peso)) {
                    return false;
                }
            }
            return true;
        });

        // Valida se medidas são obrigatórias
        Validator::extend('medidaObrigatoria', function ($attribute, $value, $parameters) use ($quantidade, $data) {
            foreach ($value as $codprodutoembalagem => $altura) {
                // se nao vende via site passa para proximo
                if (!($data['vendesite'][$codprodutoembalagem]??false)) {
                    continue;
                }
                // as medidas da unidade nao estao preenchidas
                if (empty($data['altura'][0]) || empty($data['largura'][0]) || empty($data['profundidade'][0])) {
                    return false;
                }
                // se medidas da embalagem nao estao preenchidas
                if (empty($data['altura'][$codprodutoembalagem]) || empty($data['largura'][$codprodutoembalagem]) || empty($data['profundidade'][$codprodutoembalagem])) {
                    return false;
                }
            }
            return true;
        });

        // Valida peso da unidade X peso da embalagem
        Validator::extend('pesoCoerente', function ($attribute, $value, $parameters) use ($quantidade, $data) {
            $unitario = (double) $value[0]??0;
            //percorre array de pesos
            foreach ($value as $codprodutoembalagem => $peso) {
                if ($codprodutoembalagem == 0) {
                    continue;
                }
                // se nao vende via site passa para proximo
                if (!($data['vendesite'][$codprodutoembalagem]??false)) {
                    continue;
                }
                if ($peso < ($unitario * (double) $quantidade[$codprodutoembalagem]) * 0.9) {
                    return false;
                }
                if ($peso > ($unitario * (double) $quantidade[$codprodutoembalagem]) * 1.2) {
                    return false;
                }
            }
            return true;
        });

        // Valida medidas da unidade X medidas da embalagem
        Validator::extend('medidaCoerente', function ($attribute, $value, $parameters) use ($quantidade, $data) {
            $unitario = ((double) $data['largura'][0]??0) * ((double) $data['altura'][0]??0) * ((double) $data['profundidade'][0]??0);
            foreach ($value as $codprodutoembalagem => $altura) {
                if ($codprodutoembalagem == 0) {
                    continue;
                }
                // se nao vende via site passa para proximo
                if (!($data['vendesite'][$codprodutoembalagem]??false)) {
                    continue;
                }
                $embalagem = ((double) $data['largura'][$codprodutoembalagem]??0) * ((double) $data['altura'][$codprodutoembalagem]??0) * ((double) $data['profundidade'][$codprodutoembalagem]??0);
                if ($embalagem < ($unitario * $quantidade[$codprodutoembalagem]) * 0.5) {
                    return false;
                }
                if ($embalagem > ($unitario * $quantidade[$codprodutoembalagem]) * 1.5) {
                    return false;
                }
            }
            return true;
        });

        // Valida peso da unidade * quantidade da embalagem
        Validator::extend('minimo', function ($attribute, $value, $parameters) {
            $minimo = (double) $parameters[0];
            foreach ($value as $codprodutoembalagem => $peso) {
                if ($peso == '') {
                    continue;
                }
                if ($peso < $minimo) {
                    return false;
                }
            }
            return true;
        });

        // Valida peso da unidade * quantidade da embalagem
        Validator::extend('maximo', function ($attribute, $value, $parameters) {
            $maximo = (double) $parameters[0];
            foreach ($value as $codprodutoembalagem => $peso) {
                if ($peso == '') {
                    continue;
                }
                if ($peso > $maximo) {
                    return false;
                }
            }
            return true;
        });

        // Valida peso da unidade * quantidade da embalagem
        Validator::extend('vendeSite', function ($attribute, $value, $parameters) use ($data) {
            foreach ($value as $codprodutoembalagem => $vendesite) {
                if (empty($vendesite)) {
                    continue;
                }
                if (!($data['site']??false)) {
                    return false;
                }
            }
            return true;
        });

        $rules = [
            'peso' => [
                'pesoObrigatorio',
                'pesoCoerente',
                'minimo:0.0001',
                'maximo:999.9999',
            ],
            'altura' => [
                'medidaObrigatoria',
                'medidaCoerente',
                'minimo:0.01',
                'maximo:999999.99',
            ],
            'largura' => [
                'minimo:0.01',
                'maximo:999999.99',
            ],
            'profundidade' => [
                'minimo:0.01',
                'maximo:999999.99',
            ],
            'vendesite' => [
                'vendeSite'
            ],
        ];

        $messages = [
            'peso.peso_obrigatorio' => 'Preencha o peso de todos as embalagens vendidas via site, e também da unidade mínima!',
            'peso.peso_coerente' => 'O peso das embalagens não está coerente com o peso da unidade mínima!',
            'peso.minimo' => 'O peso deve ser superior à 0,0001 Kilograma!',
            'peso.maximo' => 'O peso deve ser inferior à 999,9999 Kilograma!',
            'altura.medida_obrigatoria' => 'Preencha as medidas de todas as embalagens vendidas via site, e também da unidade mínima!',
            'altura.medida_coerente' => 'As medidas das embalagens não estão coerentes com as medidas da unidade mínima!',
            'altura.minimo' => 'A altura deve ser superior à 0,01 Centímetros!',
            'altura.maximo' => 'A altura deve ser inferior à 999999,99 Centímetros!',
            'largura.minimo' => 'A largura deve ser superior à 0,0001 Centímetros!',
            'largura.maximo' => 'A largura deve ser inferior à 999999,99 Centímetros!',
            'profundidade.minimo' => 'A profundidade deve ser superior à 0,0001 Centímetros!',
            'profundidade.maximo' => 'A profundidade deve ser inferior à 999999,99 Centímetros!',
            'vendesite.vende_site' => 'Para habilitar vendas pelo site, deve-se marcar como disponível no site!',
        ];


        $this->validator = Validator::make($data, $rules, $messages);

        return $this->validator->passes();

        return true;
    }

    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }

        if ($this->model->ImagemS->count() > 0) {
            return 'Produto sendo utilizada em "Produtoimagem"!';
        }

        if ($this->model->ProdutoVariacaoS->count() > 0) {
            return 'Produto sendo utilizada em "ProdutoVariacao"!';
        }

        if ($this->model->ProdutoBarraS->count() > 0) {
            return 'Produto sendo utilizada em "ProdutoBarra"!';
        }

        if ($this->model->ProdutoEmbalagemS->count() > 0) {
            return 'Produto sendo utilizada em "ProdutoEmbalagem"!';
        }

        if ($this->model->ProdutoHistoricoPrecoS->count() > 0) {
            return 'Produto sendo utilizada em "ProdutoHistoricoPreco"!';
        }

        return false;
    }

    public function listing($filters = [], $sort = [], $start = null, $length = null) {

        // Query da Entidade
        $qry = Produto::query();

        // Filtros
        if (!empty($filters['codproduto'])) {
            $qry->where('codproduto', '=', $filters['codproduto']);
        }

        if(!empty($filters['barras'])) {

            $barras = $filters['barras'];

            $qry->whereIn('codproduto', function ($query) use ($barras) {
                $query->select('codproduto')
                    ->from('tblprodutobarra')
                    ->where('barras', 'ilike', "%$barras%");
            });
        }

        if (!empty($filters['produto'])) {
            $qry->palavras('produto', $filters['produto']);
        }

        if (!empty($filters['referencia'])) {
            $qry->palavras('referencia', $filters['referencia']);
        }

        if(empty($filters['preco_de']) && !empty($filters['preco_ate'])) {

            $sql = "codproduto in (
                        select pe.codproduto
                        from tblprodutoembalagem pe
                        inner join tblproduto p on (p.codproduto = pe.codproduto)
                        where coalesce(pe.preco, pe.quantidade * p.preco) <= {$filters['preco_ate']}
                        or p.preco <= {$filters['preco_ate']}
                    )
                    ";

            $qry->whereRaw($sql);

        }

        if(!empty($filters['preco_de']) && !empty($filters['preco_ate'])) {

            $sql = "codproduto in (
                        select pe.codproduto
                        from tblprodutoembalagem pe
                        inner join tblproduto p on (p.codproduto = pe.codproduto)
                        where coalesce(pe.preco, pe.quantidade * p.preco) between {$filters['preco_de']} and {$filters['preco_ate']}
                        or p.preco between {$filters['preco_de']} and {$filters['preco_ate']}
                            )
                    ";

            $qry->whereRaw($sql);

        }

        if (!empty($filters['codmarca'])) {
            $qry->where('codmarca', '=', $filters['codmarca']);
        }

        if(!empty($filters['codsubgrupoproduto'])) {

            $qry->where('codsubgrupoproduto', $filters['codsubgrupoproduto']);

        } elseif (!empty($filters['codgrupoproduto'])) {

            $qry->join('tblsubgrupoproduto', 'tblsubgrupoproduto.codsubgrupoproduto', '=', 'tblproduto.codsubgrupoproduto');
            $qry->where('tblsubgrupoproduto.codgrupoproduto', $filters['codgrupoproduto']);

        } elseif (!empty($filters['codfamiliaproduto'])) {

            $qry->join('tblsubgrupoproduto', 'tblsubgrupoproduto.codsubgrupoproduto', '=', 'tblproduto.codsubgrupoproduto');
            $qry->join('tblgrupoproduto', 'tblgrupoproduto.codgrupoproduto', '=', 'tblsubgrupoproduto.codgrupoproduto');
            $qry->where('tblgrupoproduto.codfamiliaproduto', $filters['codfamiliaproduto']);

        } elseif (!empty($filters['codsecaoproduto'])) {

            $qry->join('tblsubgrupoproduto', 'tblsubgrupoproduto.codsubgrupoproduto', '=', 'tblproduto.codsubgrupoproduto');
            $qry->join('tblgrupoproduto', 'tblgrupoproduto.codgrupoproduto', '=', 'tblsubgrupoproduto.codgrupoproduto');
            $qry->join('tblfamiliaproduto', 'tblfamiliaproduto.codfamiliaproduto', '=', 'tblgrupoproduto.codfamiliaproduto');
            $qry->where('tblfamiliaproduto.codsecaoproduto', $filters['codsecaoproduto']);

        }

        if (!empty($filters['codtributacao'])) {
            $qry->where('codtributacao', '=', $filters['codtributacao']);
        }

        if (!empty($filters['codncm'])) {
            $qry->where('codncm', '=', $filters['codncm']);
        }

        if (!empty($filters['site'])) {
            $qry->where('site', '=', $filters['site']);
        }

        if(!empty($filters['criacao_de'])) {
            $qry->where('criacao', '>=', $filters['criacao_de']);
        }

        if(!empty($filters['criacao_ate'])) {
            $qry->where('criacao', '<=', $filters['criacao_ate']);
        }

        if(!empty($filters['alteracao_de'])) {
            $qry->where('alteracao', '>=', $filters['alteracao_de']);
        }

        if(!empty($filters['alteracao_ate'])) {
            $qry->where('alteracao', '<=', $filters['alteracao_ate']);
        }










        switch ($filters['inativo']) {
            case 2: //Inativos
                $qry = $qry->inativo();
                break;

            case 9: //Todos
                break;

            case 1: //Ativos
            default:
                $qry = $qry->ativo();
                break;
        }

        $count = $qry->count();

        // Paginacao
        if (!empty($start)) {
            $qry->offset($start);
        }
        if (!empty($length)) {
            $qry->limit($length);
        }

        // Ordenacao
        foreach ($sort as $s) {
            $qry->orderBy($s['column'], $s['dir']);
        }

        // Registros
        return [
            'recordsFiltered' => $count
            , 'recordsTotal' => Produto::count()
            , 'data' => $qry->get()
        ];

    }

    public function getArraySaldoEstoque()
    {

        // Array de Retorno
        $arrRet = [
            'local' => [],
            //'total' => [],
        ];

        // Array com Totais
        $arrTotal = [
            'estoqueminimo' => null,
            'estoquemaximo' => null,
            'fisico' => [
                'saldoquantidade' => null,
                'saldovalor' => null,
                'customedio' => null,
                'ultimaconferencia' => null,
            ],
            'fiscal' => [
                'saldoquantidade' => null,
                'saldovalor' => null,
                'customedio' => null,
                'ultimaconferencia' => null,
            ],
            'variacao' => [],
        ];

        // Array com Totais Por Variacao
        $pvs = $this->model->ProdutoVariacaoS()->orderBy(DB::raw("coalesce(variacao, '')"), 'ASC')->get();
        foreach ($pvs as $pv) {
            $arrTotalVar[$pv->codprodutovariacao] = [
                'codprodutovariacao' => $pv->codprodutovariacao,
                'variacao' => $pv->variacao,
                'estoqueminimo' => null,
                'estoquemaximo' => null,
                'corredor' => null,
                'prateleira' => null,
                'coluna' => null,
                'bloco' => null,
                'fisico' => [
                    'saldoquantidade' => null,
                    'saldovalor' => null,
                    'customedio' => null,
                    'ultimaconferencia' => null,
                ],
                'fiscal' => [
                    'saldoquantidade' => null,
                    'saldovalor' => null,
                    'customedio' => null,
                    'ultimaconferencia' => null,
                ],
            ];
        }

        // Percorrre todos os Locais
        foreach (EstoqueLocal::ativo()->orderBy('codestoquelocal', 'asc')->get() as $el) {

            // Array com Totais por Local
            $arrLocal = [
                'codestoquelocal' => $el->codestoquelocal,
                'estoquelocal' => $el->estoquelocal,
                'estoqueminimo' => null,
                'estoquemaximo' => null,
                'fisico' => [
                    'saldoquantidade' => null,
                    'saldovalor' => null,
                    'customedio' => null,
                    'ultimaconferencia' => null,
                ],
                'fiscal' => [
                    'saldoquantidade' => null,
                    'saldovalor' => null,
                    'customedio' => null,
                    'ultimaconferencia' => null,
                ],
                'variacao' => [],
            ];


            foreach ($pvs as $pv) {

                // Array com Saldo de Cada EstoqueLocalProdutoVariacao
                $arrVar = [
                    'codprodutovariacao' => $pv->codprodutovariacao,
                    'variacao' => $pv->variacao,
                    'codestoquelocalprodutovariacao' => null,
                    'estoqueminimo' => null,
                    'estoquemaximo' => null,
                    'corredor' => null,
                    'prateleira' => null,
                    'coluna' => null,
                    'bloco' => null,
                    'fisico' => [
                        'codestoquesaldo' => null,
                        'saldoquantidade' => null,
                        'saldovalor' => null,
                        'customedio' => null,
                        'ultimaconferencia' => null,
                    ],
                    'fiscal' => [
                        'codestoquesaldo' => null,
                        'saldoquantidade' => null,
                        'saldovalor' => null,
                        'customedio' => null,
                        'ultimaconferencia' => null,
                    ],
                ];

                //Se já existe a combinação de Variacao para o Local
                if ($elpv = $pv->EstoqueLocalProdutoVariacaoS()->where('codestoquelocal', $el->codestoquelocal)->first()) {

                    $arrVar['codestoquelocalprodutovariacao'] = $elpv->codestoquelocalprodutovariacao;

                    //Acumula Estoque Mínimo
                    $arrVar['estoqueminimo'] = $elpv->estoqueminimo;
                    if (!empty($elpv->estoqueminimo)) {
                        $arrLocal['estoqueminimo'] += $elpv->estoqueminimo;
                        $arrTotal['estoqueminimo'] += $elpv->estoqueminimo;
                        $arrTotalVar[$pv->codprodutovariacao]['estoqueminimo'] += $elpv->estoqueminimo;
                    }

                    //Acumula Estoque Máximo
                    $arrVar['estoquemaximo'] = $elpv->estoquemaximo;
                    if (!empty($elpv->estoquemaximo)) {
                        $arrLocal['estoquemaximo'] += $elpv->estoquemaximo;
                        $arrTotal['estoquemaximo'] += $elpv->estoquemaximo;
                        $arrTotalVar[$pv->codprodutovariacao]['estoquemaximo'] += $elpv->estoquemaximo;
                    }

                    $arrVar['corredor'] = $elpv->corredor;
                    if (!empty($elpv->corredor)) {
                        $arrLocal['corredor'] = $elpv->corredor;
                    }

                    $arrVar['prateleira'] = $elpv->prateleira;
                    if (!empty($elpv->prateleira)) {
                        $arrLocal['prateleira'] = $elpv->prateleira;
                    }

                    $arrVar['coluna'] = $elpv->coluna;
                    if (!empty($elpv->coluna)) {
                        $arrLocal['coluna'] = $elpv->coluna;
                    }

                    $arrVar['bloco'] = $elpv->bloco;
                    if (!empty($elpv->bloco)) {
                        $arrLocal['bloco'] = $elpv->bloco;
                    }

                    //Percorre os Saldos Físico e Fiscal
                    foreach($elpv->EstoqueSaldoS as $es) {

                        $tipo = ($es->fiscal == true)?'fiscal':'fisico';

                        $arrVar[$tipo]["codestoquesaldo"] = $es->codestoquesaldo;

                        //Acumula as quantidades de Saldo
                        $arrVar[$tipo]["saldoquantidade"] = $es->saldoquantidade;
                        $arrLocal[$tipo]["saldoquantidade"] += $es->saldoquantidade;
                        $arrTotal[$tipo]["saldoquantidade"] += $es->saldoquantidade;
                        $arrTotalVar[$pv->codprodutovariacao][$tipo]["saldoquantidade"] += $es->saldoquantidade;

                        //Acumula os valores de Saldo
                        $arrVar[$tipo]["saldovalor"] = $es->saldovalor;
                        $arrLocal[$tipo]["saldovalor"] += $es->saldovalor;
                        $arrTotal[$tipo]["saldovalor"] += $es->saldovalor;
                        $arrTotalVar[$pv->codprodutovariacao][$tipo]["saldovalor"] += $es->saldovalor;

                        $arrVar[$tipo]["customedio"] = $es->customedio;

                        $arrVar[$tipo]["ultimaconferencia"] = $es->ultimaconferencia;

                        //Pega a data de conferência mais antiga para o total do Local
                        if (empty($arrLocal[$tipo]["ultimaconferencia"])) {
                            $arrLocal[$tipo]["ultimaconferencia"] = $es->ultimaconferencia;
                        } elseif (!empty($es->ultimaconferencia) && $es->ultimaconferencia < $arrLocal[$tipo]["ultimaconferencia"]) {
                            $arrLocal[$tipo]["ultimaconferencia"] = $es->ultimaconferencia;
                        }

                        //Pega a data de conferência mais antiga para o total da variacao
                        if (empty($arrTotalVar[$pv->codprodutovariacao][$tipo]["ultimaconferencia"])) {
                            $arrTotalVar[$pv->codprodutovariacao][$tipo]["ultimaconferencia"] = $es->ultimaconferencia;
                        } elseif (!empty($es->ultimaconferencia) && $es->ultimaconferencia < $arrTotalVar[$pv->codprodutovariacao][$tipo]["ultimaconferencia"]) {
                            $arrTotalVar[$pv->codprodutovariacao][$tipo]["ultimaconferencia"] = $es->ultimaconferencia;
                        }

                        //Pega a data de conferência mais antiga para o total geral
                        if (empty($arrTotal[$tipo]["ultimaconferencia"])) {
                            $arrTotal[$tipo]["ultimaconferencia"] = $es->ultimaconferencia;
                        } elseif (!empty($es->ultimaconferencia) && $es->ultimaconferencia < $arrTotal[$tipo]["ultimaconferencia"]) {
                            $arrTotal[$tipo]["ultimaconferencia"] = $es->ultimaconferencia;
                        }

                    }

                }

                // Adiciona variacao ao array de locais
                $arrLocal['variacao'][$pv->codprodutovariacao] = $arrVar;

            }

            // Calcula o custo médio do Local
            if ($arrLocal['fisico']['saldoquantidade'] > 0)
                $arrLocal['fisico']['customedio'] = $arrLocal['fisico']['saldovalor'] / $arrLocal['fisico']['saldoquantidade'];
            if ($arrLocal['fiscal']['saldoquantidade'] > 0)
                $arrLocal['fiscal']['customedio'] = $arrLocal['fiscal']['saldovalor'] / $arrLocal['fiscal']['saldoquantidade'];

            // Adiciona local no array de retorno
            $arrRet['local'][$el->codestoquelocal] = $arrLocal;

        }

        // Calcula o custo médio dos totais de cada variacao
        foreach($arrTotalVar as $codvariacao => $arr) {
            if ($arrTotalVar[$codvariacao]['fisico']['saldoquantidade'] > 0)
                $arrTotalVar[$codvariacao]['fisico']['customedio'] = $arrTotalVar[$codvariacao]['fisico']['saldovalor'] / $arrTotalVar[$codvariacao]['fisico']['saldoquantidade'];
            if ($arrTotalVar[$codvariacao]['fiscal']['saldoquantidade'] > 0)
                $arrTotalVar[$codvariacao]['fiscal']['customedio'] = $arrTotalVar[$codvariacao]['fiscal']['saldovalor'] / $arrTotalVar[$codvariacao]['fiscal']['saldoquantidade'];
        }

        // Adiciona totais das variações ao array de totais
        $arrTotal['variacao'] = $arrTotalVar;

        // calcula o custo médio do total
        if ($arrTotal['fisico']['saldoquantidade'] > 0)
            $arrTotal['fisico']['customedio'] = $arrTotal['fisico']['saldovalor'] / $arrTotal['fisico']['saldoquantidade'];
        if ($arrTotal['fiscal']['saldoquantidade'] > 0)
            $arrTotal['fiscal']['customedio'] = $arrTotal['fiscal']['saldovalor'] / $arrTotal['fiscal']['saldoquantidade'];

        // Adiciona totais no array de retorno
        $arrRet['local']['total'] = $arrTotal;
        //$arrRet['total'] = $arrTotal;


        //retorna
        return $arrRet;
    }

    public function detalhes ($model = null, $codestoquelocal = null) {

        if (empty($model)) {
            $model = $this->model;
        }

        $produto = $model;

        //Embalagem
        $embalagens = collect();
        $embalagens[0] = (object)[
            'codprodutoembalagem' => null,
            'codembalagem' => null,
            'sigla' => $model->UnidadeMedida->sigla,
            'quantidade' => 1,
            'preco' => $model->preco,
            'precocalculado' => false,
            'variacao' => collect(),
        ];
        foreach ($model->ProdutoEmbalagemS()->orderBy('quantidade')->get() as $pe) {
            $preco = $pe->preco;
            $precocalculado = false;
            if (empty($preco)) {
                $preco = $model->preco * $pe->quantidade;
                $precocalculado = true;
            }
            $embalagens[$pe->codprodutoembalagem] = (object)[
                'codprodutoembalagem' => $pe->codprodutoembalagem,
                'codembalagem' => $pe->codembalagem,
                'sigla' => $pe->UnidadeMedida->sigla,
                'quantidade' => $pe->quantidade,
                'preco' => $preco,
                'precocalculado' => $precocalculado,
                'variacao' => collect(),
            ];
        }

        $filtro_estoquelocal = empty($codestoquelocal)?'':"and elpv.codestoquelocal = $codestoquelocal";
        $sql_saldos = "
            select pv.codprodutovariacao, sum(es.saldoquantidade) as saldoquantidade
            from tblprodutovariacao pv
            inner join tblestoquelocalprodutovariacao elpv on (elpv.codprodutovariacao = pv.codprodutovariacao $filtro_estoquelocal)
            inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
            where pv.codproduto = {$model->codproduto}
            group by pv.codprodutovariacao, pv.variacao
            order by pv.codprodutovariacao
            ";
        $saldos = collect(DB::select($sql_saldos));
        $saldos = $saldos->keyBy('codprodutovariacao');

        $variacao = collect();
        foreach ($model->ProdutoVariacaoS()->orderByRaw('variacao asc nulls first')->get() as $pv) {

            foreach ($pv->ProdutoBarraS()->orderBy('barras')->get() as $pb) {
                $codprodutoembalagem = $pb->codprodutoembalagem;
                if (empty($codprodutoembalagem)) {
                    $codprodutoembalagem = 0;
                }

                if (!isset($embalagens[$codprodutoembalagem]->variacao[$pv->codprodutovariacao])) {
                    $saldo = !empty($saldos[$pv->codprodutovariacao])?$saldos[$pv->codprodutovariacao]->saldoquantidade:null;
                    $saldo = floor($saldo / $embalagens[$codprodutoembalagem]->quantidade);
                    $variacao = (object)[
                        'codprodutovariacao' => $pv->codprodutovariacao,
                        'variacao' => $pv->variacao,
                        'marca' => empty($pv->codmarca)?'':$pv->Marca->marca,
                        'saldoquantidade' => $saldo,
                        'barras' => collect(),
                    ];
                    $embalagens[$codprodutoembalagem]->variacao[$pv->codprodutovariacao] = $variacao;
                }

                $barras = (object)[
                    'codprodutobarra' => $pb->codprodutobarra,
                    'barras' => $pb->barras,
                ];

                $embalagens[$codprodutoembalagem]->variacao[$pv->codprodutovariacao]->barras[$pb->codprodutobarra] = $barras;

            }
        }

        $repo_imagem = new ProdutoImagemRepository();
        $imagem = $repo_imagem->buscaPorProdutos($model->codproduto);

        $produto->imagens = $imagem[$model->codproduto];
        $produto->embalagens = $embalagens;
        $produto->variacoes = $variacao;

        return $produto;
    }

    public function alterarImagemPadrao ($codimagem = null, $codprodutoembalagem = null, $codprodutovariacao = null) {

        if (!empty($codimagem)) {
            if (!$pi = $this->model->ProdutoImagemS()->where('codimagem', $codimagem)->first()) {
                return false;
            }
        } else {
            if (!$pi = $this->model->ProdutoImagemS()->orderBy('ordem')->first()) {
                return false;
            }
        }

        if (!empty($codprodutoembalagem)) {
            $repo = new ProdutoEmbalagemRepository();
            $repo->findOrFail($codprodutoembalagem);
            return $repo->update(null, ['codprodutoimagem' => $pi->codprodutoimagem]);
        }

        if (!empty($codprodutovariacao)) {
            $repo = new ProdutoVariacaoRepository();
            $repo->findOrFail($codprodutovariacao);
            return $repo->update(null, ['codprodutoimagem' => $pi->codprodutoimagem]);
        }

        return $this->update(null, ['codprodutoimagem' => $pi->codprodutoimagem]);
    }

    public function alterarImagemOrdem($codimagemS) {

        $i = 1;
        foreach ($codimagemS as $codimagem) {
            if (!$this->model->ProdutoImagemS()->where('codimagem', $codimagem)->update(['ordem' => $i])) {
                return false;
            }
            $i++;
        }
        return true;
    }

    public function setarImagemPadrao() {
        $codimagem = $this->model->ImagemS->first()->codimagem;
        $this->alterarImagemPadrao($codimagem, null, null);
    }

    public function listingPrecoEmbalagens($produtoembalagens, $preco, $unidademedida) {
        $embalagens = [[
            'preco' => formataNumero($preco),
            'embalagem' => $unidademedida
        ]];

        foreach($produtoembalagens as $pe){
            if(empty($pe->preco)) {
                $precoembal    public static function find(int $id)
    {
        return app('App\\Models\\' . static::$modelClass)::find($id);
    }
agens = formataNumero($preco * $pe->quantidade);
            } else {
                $precoembalagens = formataNumero($pe->preco);
            }

        $embalagens[] = ['preco'=> $precoembalagens,'embalagem' => $pe->descricao];

        }

        return $embalagens;
    }

    public function listingProdutoVariacao($pvs, $unidademedida) {
        $variacoes = [];
        foreach ($pvs as $pv) {

            $pbs = $pv->ProdutoBarraS()->leftJoin('tblprodutoembalagem as pe', 'pe.codprodutoembalagem', '=', 'tblprodutobarra.codprodutoembalagem')
                        ->orderBy(DB::raw('coalesce(pe.quantidade, 0)'), 'ASC')
                        ->with('ProdutoEmbalagem')->get();

            foreach ($pbs as $pb) {
                $barras = [
                    'barras' => $pb->barras,
                    'embalagem' => !empty($pb->codprodutoembalagem) ? $pb->ProdutoEmbalagem->descricao : $unidademedida
                ];
            }

            $variacoes[] = [
                'variacao'      => $pv->variacao ?? 'Sem Variação',
                'marca'         => $pv->marca ?? null,
                'referencia'    => $pv->referencia,
                'barras'        => $barras
            ];
        }

        return $variacoes;
    }
}
*/
