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

    public static function calculaVendaDia ($vendas)
    {
        if ($vendas->count() == 0) {
            return null;
        }

        /*
        foreach ($vendas as $venda) {
            echo "{$venda->mes} => {$venda->quantidade}\n";
        }
        */

        // Ignora dois meses com menos movimentacao
        if ($vendas->count() > 4) {
            $vendas->sortBy('quantidade');
            $vendas->shift();
            $vendas->shift();
        }

        // Ignora Janeiro
        if ($vendas->count() > 4) {
            $vendas = $vendas->reject(function ($venda) {
                return $venda->mes->month == 1;
            });
        }

        // Ignora Fevereiro
        if ($vendas->count() > 4) {
            $vendas = $vendas->reject(function ($venda) {
                return $venda->mes->month == 2;
            });
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

        // calcula vendadia
        $vendadia = $quantidade / $dias;

        return $vendadia;

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

        DB::listen(function($sql) {
            echo "Executando\n\n{$sql->sql}\n\nTime {$sql->time}";
            echo "---\n\n\n";
        });

        $agora = Carbon::now();

        // Cria combinacoes de EstoqueLocalProdutoVariacao que ainda nao existam na tabela
        static::criaCombinacoesInexistentes();

        // Filtra de acordo com os parametros recebidos
        $qry = EstoqueLocalProdutoVariacao::query();
        $qry->select([
            'tblestoquelocalprodutovariacao.codestoquelocalprodutovariacao',
            'tblestoquelocalprodutovariacao.codestoquelocal',
            'tblestoquelocalprodutovariacao.codprodutovariacao',
            'tblprodutovariacao.codproduto',
            'tblproduto.codmarca',
            'tblmarca.estoqueminimodias',
            'tblmarca.estoquemaximodias',
            'tblestoquelocal.deposito',
        ]);
        $qry->join('tblestoquelocal', 'tblestoquelocal.codestoquelocal', 'tblestoquelocalprodutovariacao.codestoquelocal');
        $qry->join('tblprodutovariacao', 'tblprodutovariacao.codprodutovariacao', 'tblestoquelocalprodutovariacao.codprodutovariacao');
        $qry->join('tblproduto', 'tblproduto.codproduto', 'tblprodutovariacao.codproduto');
        $qry->join('tblmarca', 'tblmarca.codmarca', 'tblproduto.codmarca');
        $qry->where('tblproduto.codtipoproduto', 0);
        $qry->whereNull('tblproduto.inativo');
        $qry->whereNull('tblestoquelocal.inativo');
        if (!empty($codestoquelocal)) {
            $qry->where('codestoquelocal', $codestoquelocal);
        }
        if (!empty($codprodutovariacao)) {
            $codprodutovariacaos = [$codprodutovariacao];
            $qry->where('tblestoquelocalprodutovariacao.codprodutovariacao', $codprodutovariacao);
        } elseif (!empty($codproduto)) {
            $codprodutovariacaos = \App\Models\ProdutoVariacao::where('codproduto', $codproduto)->get()->pluck('codprodutovariacao');
            $qry->where('tblprodutovariacao.codproduto', $codproduto);
        }
        $regs = $qry->get();

        // Busca Vendas
        $qry = \App\Models\EstoqueLocalProdutoVariacaoVenda::select(['codestoquelocalprodutovariacao', 'mes', 'quantidade', 'valor']);
        $mes_inicial = $agora->startOfMonth()->addYears(-1)->addMonths(-1);
        $qry->where('mes', '>=', $mes_inicial);
        $qry->whereNotNull('quantidade');
        $qry->where('quantidade', '>', 0);
        if (!empty($codprodutovariacao) or !empty($codestoquelocal) or !empty($codproduto)) {
            $qry->whereIn('codestoquelocalprodutovariacao', $regs->pluck('codestoquelocalprodutovariacao'));
        }
        $vendas = $qry->get()->groupBy('codestoquelocalprodutovariacao');

        echo "\n\n\n\nDAQUI PRA BAIXO SO PODE UPDATE\n\n\n\n\n";

        // Percorre registros calculando Estoque Minimo e Maximo das Lojas
        $vendadia_variacao = [];
        $i = 0;
        foreach ($regs as $reg) {

            $i++;
            // echo "{$i} {$variacao->codproduto} {$reg->codprodutovariacao} {$reg->codestoquelocal}\n";
            /*
            */

            if ($i > 5000) {
                dd('parou em 5000 registros');
                die();
            }

            // Calcula Media de Vendas para O Local
            // echo "Iniciando {$reg->codestoquelocal} {$reg->codprodutovariacao}\n";
            $vendadia = null;
            if (!empty($vendas[$reg->codestoquelocalprodutovariacao])) {
                $vendadia = static::calculaVendaDia($vendas[$reg->codestoquelocalprodutovariacao]);
            }


            // Acumula Media de Vendas para a Variacao
            if (!isset($vendadia_variacao[$reg->codprodutovariacao])) {
                $vendadia_variacao[$reg->codprodutovariacao] = (object) [
                    'codprodutovariacao' => $reg->codprodutovariacao,
                    'estoqueminimodias' => $reg->estoqueminimodias,
                    'estoquemaximodias' => $reg->estoquemaximodias,
                    'vendadia' => 0
                ];
            }
            $vendadia_variacao[$reg->codprodutovariacao]->vendadia += $vendadia;

            // Calcula Minimo e Maximo para as Lojas
            $minimo = null;
            $maximo = null;
            if (!$reg->deposito) {
                $minimo = ceil($vendadia * $reg->estoqueminimodias);
                if ($minimo < 1) {
                    $minimo = 1;
                }
                $maximo = ceil($vendadia * $reg->estoquemaximodias);
                if ($maximo <= $minimo) {
                    $maximo = $minimo + 1;
                }
            }

            // Atualiza no Banco de Dados
            EstoqueLocalProdutoVariacao::where('codestoquelocalprodutovariacao', $reg->codestoquelocalprodutovariacao)->update([
                'vendadiaquantidadeprevisao' => $vendadia,
                'estoqueminimo' => $minimo,
                'estoquemaximo' => $maximo,
                'alteracao' => $agora
            ]);
        }

        // Se estava calculando para todos EstoqueLocal
        if (empty($codestoquelocal)) {

            // Busca todos EstoqueLocal marcados como 'deposito'
            $depositos = \App\Models\EstoqueLocal::where('deposito', true)->pluck('codestoquelocal');

            // Percorre as vendadias de venda acumuladas para o ProdutoVariacao
            foreach ($vendadia_variacao as $cod => $item) {

                // Calcula o Minimo e Maximo baseado na venda de todas as lojas somadas
                $minimo = ceil($item->vendadia * $item->estoqueminimodias);
                if ($minimo == 0) {
                    $minimo = 1;
                }
                $maximo = ceil($item->vendadia * $item->estoquemaximodias);
                if ($maximo <= $minimo) {
                    $maximo = $minimo + 1;
                }

                // Atualiza no Banco de Dados
                EstoqueLocalProdutoVariacao::where('codprodutovariacao', $cod)->whereIn('codestoquelocal', $depositos)->update([
                    'vendadiaquantidadeprevisao' => $item->vendadia,
                    'estoqueminimo' => $minimo,
                    'estoquemaximo' => $maximo,
                    'alteracao' => $agora
                ]);

                // Atualiza no Banco de Dados Soma das vendas do ProdutoVariacao
                \App\Models\ProdutoVariacao::where('codprodutovariacao', $cod)->update([
                    'vendadiaquantidadeprevisao' => $item->vendadia
                ]);
            }

            // Limpa registros inativos
            $qry = EstoqueLocalProdutoVariacao::query();
            $qry->join('tblestoquelocal', 'tblestoquelocal.codestoquelocal', 'tblestoquelocalprodutovariacao.codestoquelocal');
            $qry->join('tblprodutovariacao', 'tblprodutovariacao.codprodutovariacao', 'tblestoquelocalprodutovariacao.codprodutovariacao');
            $qry->join('tblproduto', 'tblproduto.codproduto', 'tblprodutovariacao.codproduto');
            $qry->where(function ($query) {
                $query->whereNotNull('tblproduto.inativo');
                $query->orWhere('tblproduto.codtipoproduto', '!=', 0);
                $query->orWhereNotNull('tblestoquelocal.inativo');
            });
            if (!empty($codestoquelocal)) {
                $qry->where('codestoquelocal', $codestoquelocal);
            }
            if (!empty($codprodutovariacao)) {
                $qry->where('tblestoquelocalprodutovariacao.codprodutovariacao', $codprodutovariacao);
            } elseif (!empty($codproduto)) {
                $qry->where('tblprodutovariacao.codproduto', $codproduto);
            }
            $regs = $qry->update([
                'vendadiaquantidadeprevisao' => null,
                'estoqueminimo' => null,
                'estoquemaximo' => null,
                'alteracao' => $agora
            ]);

            // Limpa registros inativos
            $qry = \App\Models\ProdutoVariacao::query();
            $qry->join('tblproduto', 'tblproduto.codproduto', 'tblprodutovariacao.codproduto');
            $qry->where(function ($query) {
                $query->whereNotNull('tblproduto.inativo');
                $query->orWhere('tblproduto.codtipoproduto', '!=', 0);
            });
            if (!empty($codprodutovariacao)) {
                $qry->where('tblprodutovariacao.codprodutovariacao', $codprodutovariacao);
            } elseif (!empty($codproduto)) {
                $qry->where('tblprodutovariacao.codproduto', $codproduto);
            }
            $regs = $qry->update([
                'vendadiaquantidadeprevisao' => null,
            ]);

        }


        return true;

    }
}
