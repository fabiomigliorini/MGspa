<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DB;
use Carbon\Carbon;

use App\Models\Produto;
use App\Models\ProdutoVariacao;
use App\Models\ProdutoBarra;
use App\Models\EstoqueMovimento;
use App\Models\EstoqueSaldo;
use App\Models\EstoqueSaldoConferencia;


/**
 * Description of ProdutoRepository
 *
 */
class ProdutoRepository extends MGRepositoryStatic
{
    public static $modelClass = 'App\\Models\\Produto';

    public static function validationRules ($model = null)
    {
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

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
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

        return $messages;
    }

    public static function details($model)
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

    public static function unificaVariacoes ($codprodutovariacaoorigem, $codprodutovariacaodestino)
    {
        $pv_origem = ProdutoVariacao::findOrFail($codprodutovariacaoorigem);
        $pv_destino = ProdutoVariacao::findOrFail($codprodutovariacaodestino);

        if ($pv_origem->codproduto != $pv_destino->codproduto) {
            //throw \Illuminate\Validation\ValidationException::withMessages([
            //    'Variações não são do mesmo produto'
            //]);
            dd('Variações não são do mesmo produto');
        }

        // Busca todos os meses que houve movimento de estoque
        $sql = '
            select
              elpv.codprodutovariacao as codprodutovariacaoorigem,
              elpv.codestoquelocal,
              es.codestoquesaldo as codestoquesaldoorigem,
              es.fiscal,
              mes.codestoquemes as codestoquemesorigem,
              mes.mes
            from tblestoquelocalprodutovariacao elpv
            inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao)
            left join tblestoquemes mes on (mes.codestoquesaldo = es.codestoquesaldo)
            where elpv.codprodutovariacao = :codprodutovariacao
        ';
        $binds = [
            'codprodutovariacao' => $codprodutovariacaoorigem
        ];
        $meses = collect(DB::select(DB::raw($sql), $binds));

        // Percorre todos meses, associando ao novo mes
        foreach ($meses as $mes) {

            // se tem mes, unifica a movimentacao do mes
            if (!empty($mes->codestoquemesorigem)) {

                // calcula novo estoquemes
                $mes->mes = Carbon::parse($mes->mes);
                $novo_mes = EstoqueMesRepository::buscaOuCria(
                    $mes->codestoquelocal,
                    $codprodutovariacaodestino,
                    $mes->fiscal,
                    $mes->mes
                );

                // Transfere movimento de estoque
                $mes->codestoquemesdestino = $novo_mes->codestoquemes;
                $mes->movimentos = EstoqueMovimento::where('codestoquemes', $mes->codestoquemesorigem)->update([
                    'codestoquemes' => $mes->codestoquemesdestino
                ]);

                if ($mes->movimentos) {

                    // recalcula Custo Médio para origem
                    $url = "https://sistema.mgpapelaria.com.br/MGLara/estoque/calcula-custo-medio/{$mes->codestoquemesorigem}";
                    $res = json_decode(file_get_contents($url));
                    if ($res->response != "Agendado") {
                        dd("Rode Manualmente... erro ao calcular custo medio do mes {$mes->codestoquemesorigem}.. $url");
                    }

                    // recalcula Custo Médio para destino
                    $url = "https://sistema.mgpapelaria.com.br/MGLara/estoque/calcula-custo-medio/{$mes->codestoquemesdestino}";
                    $res = json_decode(file_get_contents($url));
                    if ($res->response != "Agendado") {
                        dd("Rode Manualmente... erro ao calcular custo medio do mes {$mes->codestoquemesdestino}.. $url");
                    }
                }
                $mes->codestoquesaldodestino = $novo_mes->codestoquesaldo;
            } else {
                $novo_saldo = EstoqueSaldoRepository::buscaOuCria(
                    $mes->codestoquelocal,
                    $codprodutovariacaodestino,
                    $mes->fiscal
                );
                $mes->codestoquesaldodestino = $novo_saldo->codestoquesaldo;
            }

            // transfere conferencias de saldo
            $mes->conferencias = EstoqueSaldoConferencia::where('codestoquesaldo', $mes->codestoquesaldoorigem)->update([
                'codestoquesaldo' => $mes->codestoquesaldodestino
            ]);

        }

        // transfere codigos de barra
        foreach ($pv_origem->ProdutoBarraS as $pb) {
            $pb->variacao = $pb->variacao??$pv_origem->variacao;
            $pb->referencia = $pb->referencia??$pv_origem->referencia;
            $pb->codmarca = $pb->codmarca??$pv_origem->codmarca;
            $pb->codprodutovariacao = $codprodutovariacaodestino;
            $pb->save();
        }

        // Apaga a variacao de origem
        $pv_origem->delete();

        return true;
    }

    public static function unificaBarras ($codprodutobarraorigem, $codprodutobarradestino)
    {

        $pb_origem = ProdutoBarra::findOrFail($codprodutobarraorigem);
        $pb_destino = ProdutoBarra::findOrFail($codprodutobarradestino);

        if ($pb_origem->codprodutovariacao != $pb_destino->codprodutovariacao) {
            dd('Barras não são da mesma Variacao!');
        }
        $regs = $pb_origem->NegocioProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->NotaFiscalProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->CupomFiscalProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->NfeTerceiroItemS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->ValeCompraModeloProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $regs = $pb_origem->ValeCompraProdutoBarraS()->where('codprodutobarra', $codprodutobarraorigem)->update([
            'codprodutobarra' => $codprodutobarradestino
        ]);
        $pb_origem->delete();

        return true;

    }
}
