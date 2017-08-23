<?php

namespace App\Repositories;

use DB;
use Carbon\Carbon;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\EstoqueLocalProdutoVariacao;

class EstoqueLocalProdutoVariacaoRepository extends MGRepositoryStatic
{
    public static $modelClass = 'App\\Models\\EstoqueLocalProdutoVariacao';

    public static function validationRules ($model = null)
    {
        $rules = [
            'codestoquelocal' => [
                'numeric',
                'required',
            ],
            'corredor' => [
                'numeric',
                'nullable',
            ],
            'prateleira' => [
                'numeric',
                'nullable',
            ],
            'coluna' => [
                'numeric',
                'nullable',
            ],
            'bloco' => [
                'numeric',
                'nullable',
            ],
            'estoqueminimo' => [
                'numeric',
                'nullable',
            ],
            'estoquemaximo' => [
                'numeric',
                'nullable',
            ],
            'codprodutovariacao' => [
                'numeric',
                'required',
            ],
            'vendabimestrequantidade' => [
                'numeric',
                'nullable',
            ],
            'vendabimestrevalor' => [
                'numeric',
                'nullable',
            ],
            'vendasemestrequantidade' => [
                'numeric',
                'nullable',
            ],
            'vendasemestrevalor' => [
                'numeric',
                'nullable',
            ],
            'vendaanoquantidade' => [
                'numeric',
                'nullable',
            ],
            'vendaanovalor' => [
                'numeric',
                'nullable',
            ],
            'vendaultimocalculo' => [
                'date',
                'nullable',
            ],
            'vencimento' => [
                'date',
                'nullable',
            ],
            'vendadiaquantidadeprevisao' => [
                'numeric',
                'nullable',
            ],
        ];

        return $rules;
    }

    public static function validationMessages ($model = null)
    {
        $messages = [
            'codestoquelocal.numeric' => 'O campo "codestoquelocal" deve ser um número!',
            'codestoquelocal.required' => 'O campo "codestoquelocal" deve ser preenchido!',
            'corredor.numeric' => 'O campo "corredor" deve ser um número!',
            'prateleira.numeric' => 'O campo "prateleira" deve ser um número!',
            'coluna.numeric' => 'O campo "coluna" deve ser um número!',
            'bloco.numeric' => 'O campo "bloco" deve ser um número!',
            'estoqueminimo.numeric' => 'O campo "estoqueminimo" deve ser um número!',
            'estoquemaximo.numeric' => 'O campo "estoquemaximo" deve ser um número!',
            'codprodutovariacao.numeric' => 'O campo "codprodutovariacao" deve ser um número!',
            'codprodutovariacao.required' => 'O campo "codprodutovariacao" deve ser preenchido!',
            'vendabimestrequantidade.numeric' => 'O campo "vendabimestrequantidade" deve ser um número!',
            'vendabimestrevalor.numeric' => 'O campo "vendabimestrevalor" deve ser um número!',
            'vendasemestrequantidade.numeric' => 'O campo "vendasemestrequantidade" deve ser um número!',
            'vendasemestrevalor.numeric' => 'O campo "vendasemestrevalor" deve ser um número!',
            'vendaanoquantidade.numeric' => 'O campo "vendaanoquantidade" deve ser um número!',
            'vendaanovalor.numeric' => 'O campo "vendaanovalor" deve ser um número!',
            'vendaultimocalculo.date' => 'O campo "vendaultimocalculo" deve ser uma data!',
            'vencimento.date' => 'O campo "vencimento" deve ser uma data!',
            'vendadiaquantidadeprevisao.numeric' => 'O campo "vendadiaquantidadeprevisao" deve ser um número!',
        ];

        return $messages;
    }

    public static function details($model)
    {
        return parent::details ($model);
    }

    public static function query(array $filter = null, array $sort = null, array $fields = null)
    {
        return parent::query ($filter, $sort, $fields);
    }

    public static function buscaOuCria($codestoquelocal, $codprodutovariacao)
    {
        if ($model = EstoqueLocalProdutoVariacao::where('codestoquelocal', $codestoquelocal)->where('codprodutovariacao', $codprodutovariacao)->first()) {
            return $model;
        }
        $model = static::new([
            'codestoquelocal' => $codestoquelocal,
            'codprodutovariacao' => $codprodutovariacao,
        ]);
        return static::save($model);
    }

    public static function calculaMediaVendaDia (EstoqueLocalProdutoVariacao $elpv)
    {
        //$vendas = $elpv->EstoqueLocalProdutoVariacaoVendaS;
        $mes_inicial = Carbon::now()->startOfMonth()->addYears(-1)->addMonths(-1);
        $vendas = $elpv->EstoqueLocalProdutoVariacaoVendaS()
            ->where('mes', '>=', $mes_inicial)
            ->whereNotNull('quantidade')
            ->where('quantidade', '>', 0)->get();

        if ($vendas->count() == 0) {
            return null;
        }

        echo "Iniciando {$elpv->codestoquelocal} {$elpv->codprodutovariacao}\n";
        foreach ($vendas as $venda) {
            echo "{$venda->mes} => {$venda->quantidade}\n";
        }

        // Ignora Janeiro e fevereiro
        if ($vendas->count() > 3) {
            $vendas = $vendas->reject(function ($venda) {
                if ($venda->mes->month == 1 or $venda->mes->month == 2) {
                    return true;
                }
                return false;
            });
        }

        // Ignora dois meses com menos movimentacao
        if ($vendas->count() > 3) {
            $vendas->sortBy('quantidade');
            $vendas->shift();
            $vendas->shift();
        }

        // Soma quantidade
        $quantidade = $vendas->sum('quantidade');

        // Soma dias dos meses
        $mes_atual = Carbon::now()->startOfMonth();
        $dias = $vendas->sum(function ($venda) use($mes_atual) {
            if ($mes_atual == $venda->mes) {
                return $mes_atual->diffInDays(Carbon::now())+1;
            }
            return $venda->mes->daysInMonth;
        });

        // calcula media
        $media = $quantidade / $dias;

        return $media;

    }

    public static function criaCombinacoesInexistentes ()
    {
        $sql = "
            insert into tblestoquelocalprodutovariacao (codestoquelocal, codprodutovariacao, criacao, alteracao)
            select el.codestoquelocal, pv.codprodutovariacao, date_trunc('second', now()), date_trunc('second', now())
            from tblestoquelocal el
            inner join tblproduto p on (p.inativo is null)
            inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
            left join tblestoquelocalprodutovariacao elpv on (elpv.codestoquelocal = el.codestoquelocal and elpv.codprodutovariacao = pv.codprodutovariacao)
            where el.inativo is null
            and elpv.codestoquelocalprodutovariacao is null
            ";
        return DB::insert($sql);
    }

    public static function calculaVenda ($codestoquelocal, $codprodutovariacao, $codproduto = null)
    {

        /*
        // Sumariza o total de venda por mes na tabela de EstoqueLocalProdutoVariacaoVenda
        if (!EstoqueLocalProdutoVariacaoVendaRepository::sumarizaPorMes()) {
            return false;
        }
        */

        /*
        DB::listen(function($sql) {
            echo "Executando\n\n{$sql->sql}\n\nTime {$sql->time}";
            echo "---\n\n\n";
        });
        */

        $agora = Carbon::now();

        // Cria combinacoes de EstoqueLocalProdutoVariacao que ainda nao existam na tabela
        static::criaCombinacoesInexistentes();

        // Filtra de acordo com os parametros recebidos
        $qry = EstoqueLocalProdutoVariacao::query();
        if (!empty($codestoquelocal)) {
            $qry->where('codestoquelocal', $codestoquelocal);
        }
        if (!empty($codprodutovariacao)) {
            $codprodutovariacaos = [$codprodutovariacao];
        }
        if (!empty($codproduto)) {
            $codprodutovariacaos = \App\Models\ProdutoVariacao::where('codproduto', $codproduto)->get()->pluck('codprodutovariacao');
        }
        if (!empty($codprodutovariacaos)) {
            $qry->whereIn('codprodutovariacao', $codprodutovariacaos);
        }

        echo "Ponto 2\n";

        // Eager Loading para trazer as vendas por mes
        /*
        $mes_inicial = Carbon::now()->startOfMonth()->addYears(-1)->addMonths(-1);
        $qry->with(['EstoqueLocalProdutoVariacaoVendaS' => function ($query) use ($mes_inicial) {
            $query->where('mes', '>=', $mes_inicial);
            $query->whereNotNull('quantidade');
            $query->where('quantidade', '>', 0);
            $query->orderBy('mes');
        }]);
        */

        // Eager Loading trazendo mais tabelas que vamos utilizar
        //$qry->with('EstoqueLocal');
        //$qry->with('ProdutoVariacao.Produto.Marca');

        $marcas = \App\Models\Marca::all();
        $marcas = $marcas->keyBy('codmarca');

        $produtos = \App\Models\Produto::all();
        $produtos = $produtos->keyBy('codproduto');

        $variacoes = \App\Models\ProdutoVariacao::all();
        $variacoes = $variacoes->keyBy('codprodutovariacao');

        /*
        */
        echo "Ponto 3\n";

        // Percorre registros calculando Estoque Minimo e Maximo das Lojas
        $media_pv = [];
        $i = 0;
        foreach ($qry->get() as $elpv) {

            $i++;
            echo "{$i} {$elpv->ProdutoVariacao->codproduto} {$elpv->codprodutovariacao} {$elpv->codestoquelocal}\n";

            // Calcula Media de Vendas para O Local
            $media = static::calculaMediaVendaDia($elpv);

            // Acumula Media de Vendas para a Variacao
            if (!isset($media_pv[$elpv->codprodutovariacao])) {
                $media_pv[$elpv->codprodutovariacao] = [
                    'ProdutoVariacao' => $elpv->ProdutoVariacao,
                    'media' => 0
                ];
            }
            $media_pv[$elpv->codprodutovariacao]['media'] += $media;

            // Calcula Minimo e Maximo para as Lojas
            $minimo = null;
            $maximo = null;
            if (!$elpv->EstoqueLocal->deposito) {
                if ($minimo == 0) {
                    $minimo = ceil($media * $marcas[$produtos[$elpv->ProdutoVariacao->codproduto]->codmarca]->estoqueminimodias);
                    $minimo = 1;
                }
                $maximo = ceil($media * $marcas[$produtos[$elpv->ProdutoVariacao->codproduto]->codmarca]->estoquemaximodias);
                if ($maximo <= $minimo) {
                    $maximo = $minimo + 1;
                }
            }

            // Atualiza no Banco de Dados
            EstoqueLocalProdutoVariacao::where('codestoquelocalprodutovariacao', $elpv->codestoquelocalprodutovariacao)->update([
                'vendadiaquantidadeprevisao' => $media,
                'estoqueminimo' => $minimo,
                'estoquemaximo' => $maximo,
                'alteracao' => $agora
            ]);
        }

        // Se estava calculando para todos EstoqueLocal
        if (empty($codestoquelocal)) {

            // Busca todos EstoqueLocal marcados como 'deposito'
            $depositos = \App\Models\EstoqueLocal::where('deposito', true)->get()->pluck('codestoquelocal');

            // Percorre as medias de venda acumuladas para o ProdutoVariacao
            foreach ($media_pv as $cod => $item) {

                // Calcula o Minimo e Maximo baseado na venda de todas as lojas somadas
                $minimo = ceil($item['media'] * $marcas[$item['ProdutoVariacao']->Produto->codmarca]->estoqueminimodias);
                if ($minimo == 0) {
                    $minimo = 1;
                }
                $maximo = ceil($item['media'] * $marcas[$item['ProdutoVariacao']->Produto->codmarca]->estoquemaximodias);
                if ($maximo <= $minimo) {
                    $maximo = $minimo + 1;
                }

                // Atualiza no Banco de Dados
                EstoqueLocalProdutoVariacao::where('codprodutovariacao', $cod)->whereIn('codestoquelocal', $depositos)->update([
                    'vendadiaquantidadeprevisao' => $item['media'],
                    'estoqueminimo' => $minimo,
                    'estoquemaximo' => $maximo,
                    'alteracao' => $agora
                ]);

                // Atualiza no Banco de Dados Soma das vendas do ProdutoVariacao
                \App\Models\ProdutoVariacao::where('codprodutovariacao', $cod)->update([
                    'vendadiaquantidadeprevisao' => $item['media']
                ]);
            }

            // Reinicializa registros de Minimo e Maximo que nao passaram pelo calculo
            $qry = EstoqueLocalProdutoVariacao::where('alteracao', '<', $agora);
            if (!empty($codprodutovariacaos)) {
                $qry->whereIn('codprodutovariacao', $codprodutovariacaos);
            }
            $qry->update([
                'vendadiaquantidadeprevisao' => null,
                'estoqueminimo' => 1,
                'estoquemaximo' => 2,
                'alteracao' => $agora
            ]);

        }

        return true;

    }
}
