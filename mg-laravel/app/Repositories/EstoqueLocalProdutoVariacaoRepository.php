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

    public static function calculaVenda ($codestoquelocal = null, $codprodutovariacao = null, $codproduto = null, $codmarca = null)
    {

        // Sumariza o total de venda por mes na tabela de EstoqueLocalProdutoVariacaoVenda
        /*
        if (!EstoqueLocalProdutoVariacaoVendaRepository::sumarizaPorMes($codestoquelocal, $codprodutovariacao, $codproduto, $codmarca)) {
            return false;
        }

        DB::listen(function($sql) {
            echo "Executando\n\n{$sql->sql}\n\nTime {$sql->time}";
            echo "---\n\n\n";
        });

        */

        // Calcula meses de corte para somatorio dos periodos de venda
        $agora = Carbon::now();
        $mes_atual = (clone $agora)->startOfMonth();
        $mes_inicial = (clone $mes_atual)->addYears(-1);
        $mes_ano = (clone $mes_atual)->addMonths(-11);
        $mes_semestre = (clone $mes_atual)->addMonths(-5);
        $mes_bimestre = (clone $mes_atual)->addMonths(-1);

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
            $qry->where('tblestoquelocalprodutovariacao.codprodutovariacao', $codprodutovariacao);
        } elseif (!empty($codproduto)) {
            $qry->where('tblprodutovariacao.codproduto', $codproduto);
        }
        if (!empty($codmarca)) {
            $qry->where('tblproduto.codmarca', $codmarca);
        }
        $regs = $qry->get();

        // Se for somente para parte dos produtos, cria variaveis com codigos para auxiliar nas filtragens
        if (!empty($codprodutovariacao) || !empty($codproduto) || !empty($codmarca)) {
            $codestoquelocalprodutovariacao_s = $regs->pluck('codestoquelocalprodutovariacao')->unique();
            $codprodutovariacao_s = $regs->pluck('codprodutovariacao')->unique();
            $codproduto_s = $regs->pluck('codproduto')->unique();
            $codmarca_s = $regs->pluck('codmarca')->unique();
        }

        // Busca Vendas
        $qry = \App\Models\EstoqueLocalProdutoVariacaoVenda::select(['codestoquelocalprodutovariacao', 'mes', 'quantidade', 'valor']);
        $qry->where('mes', '>=', $mes_inicial);
        $qry->whereNotNull('quantidade');
        $qry->where('quantidade', '>', 0);
        if (!empty($codestoquelocalprodutovariacao_s)) {
            $qry->whereIn('codestoquelocalprodutovariacao', $codestoquelocalprodutovariacao_s);
        }
        $vendas = $qry->get()->groupBy('codestoquelocalprodutovariacao');

        // Percorre registros calculando Estoque Minimo e Maximo das Lojas
        $totais = collect();
        $i = 0;
        foreach ($regs as $reg) {
            $i++;

            if (($i % 100) == 0) {
                echo "$i {$reg->codproduto} \n";
            }

            // Calcula Media de Vendas para O Local
            $vendadia = 0;
            $vendaanovalor = 0;
            $vendaanoquantidade = 0;
            $vendasemestrevalor = 0;
            $vendasemestrequantidade = 0;
            $vendabimestrevalor = 0;
            $vendabimestrequantidade = 0;

            if (!empty($vendas[$reg->codestoquelocalprodutovariacao])) {

                // Calcula media de venda por Dia
                $venda = $vendas[$reg->codestoquelocalprodutovariacao];
                $vendadia = static::calculaVendaDia((clone $venda));

                // Soma venda Ano
                $filtrado = (clone $venda)->filter(function ($item) use ($mes_ano) {
                    return $item->mes->gte($mes_ano);
                });
                $vendaanovalor = $filtrado->sum('valor');
                $vendaanoquantidade = $filtrado->sum('quantidade');

                // Soma venda Semestre
                $filtrado = $filtrado->filter(function ($item) use ($mes_semestre) {
                    return $item->mes->gte($mes_semestre);
                });
                $vendasemestrevalor = $filtrado->sum('valor');
                $vendasemestrequantidade = $filtrado->sum('quantidade');

                // Soma venda Bimestre
                $filtrado = $filtrado->filter(function ($item) use ($mes_bimestre) {
                    return $item->mes->gte($mes_bimestre);
                });
                $vendabimestrevalor = $filtrado->sum('valor');
                $vendabimestrequantidade = $filtrado->sum('quantidade');

            }

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

            // Acumula dados de Vendas para a Variacao
            if (!isset($totais[$reg->codprodutovariacao])) {
                $totais[$reg->codprodutovariacao] = (object) [
                    'codproduto' => $reg->codproduto,
                    'codprodutovariacao' => $reg->codprodutovariacao,
                    'estoqueminimodias' => $reg->estoqueminimodias,
                    'estoquemaximodias' => $reg->estoquemaximodias,
                    'vendadia' => 0,
                ];
            }
            $totais[$reg->codprodutovariacao]->vendadia += $vendadia;

            // Atualiza no Banco de Dados
            EstoqueLocalProdutoVariacao::where('codestoquelocalprodutovariacao', $reg->codestoquelocalprodutovariacao)->update([
                'vendadiaquantidadeprevisao' => $vendadia,
                'estoqueminimo' => $minimo,
                'estoquemaximo' => $maximo,
                'vendaanovalor' => $vendaanovalor,
                'vendaanoquantidade' => $vendaanoquantidade,
                'vendasemestrevalor' => $vendasemestrevalor,
                'vendasemestrequantidade' => $vendasemestrequantidade,
                'vendabimestrevalor' => $vendabimestrevalor,
                'vendabimestrequantidade' => $vendabimestrequantidade,
                'vendaultimocalculo' => $agora,
            ]);
        }

        // Se estava calculando para todos Locais
        if (empty($codestoquelocal)) {

            // Busca todos EstoqueLocal marcados como 'deposito'
            $depositos = \App\Models\EstoqueLocal::where('deposito', true)->pluck('codestoquelocal');

            // Percorre as vendadias de venda acumuladas para o ProdutoVariacao
            foreach ($totais as $cod => $total) {

                // Calcula o Minimo e Maximo baseado na venda de todas as lojas somadas
                $minimo = ceil($total->vendadia * $total->estoqueminimodias);
                if ($minimo == 0) {
                    $minimo = 1;
                }
                $maximo = ceil($total->vendadia * $total->estoquemaximodias);
                if ($maximo <= $minimo) {
                    $maximo = $minimo + 1;
                }

                // Atualiza no Banco de Dados Estoque Minimo e Maximo do Deposito
                EstoqueLocalProdutoVariacao::where('codprodutovariacao', $cod)->whereIn('codestoquelocal', $depositos)->update([
                    'estoqueminimo' => $minimo,
                    'estoquemaximo' => $maximo,
                    'vendaultimocalculo' => $agora
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
            if (!empty($codmarca)) {
                $qry->where('tblproduto.codmarca', $codmarca);
            }
            $regs = $qry->update([
                'vendadiaquantidadeprevisao' => null,
                'estoqueminimo' => null,
                'estoquemaximo' => null,
                'vendaanovalor' => null,
                'vendaanoquantidade' => null,
                'vendasemestrevalor' => null,
                'vendasemestrequantidade' => null,
                'vendabimestrevalor' => null,
                'vendabimestrequantidade' => null,
                'vendaultimocalculo' => null,
            ]);

        }

        // Atualiza totais da Variacao
        $filtro = '';
        if (!empty($codprodutovariacao_s)) {
            $filtro = implode(', ', $codprodutovariacao_s->toArray());
            $filtro = "where elpv.codprodutovariacao in ($filtro)";
        }
        $sql = "
            update tblprodutovariacao
            set vendadiaquantidadeprevisao = iq.vendadiaquantidadeprevisao,
                estoqueminimo = iq.estoqueminimo,
                estoquemaximo = iq.estoquemaximo,
                vendaultimocalculo = iq.vendaultimocalculo,
                vendabimestrequantidade = iq.vendabimestrequantidade,
                vendabimestrevalor = iq.vendabimestrevalor,
                vendasemestrequantidade = iq.vendasemestrequantidade,
                vendasemestrevalor = iq.vendasemestrevalor,
                vendaanoquantidade = iq.vendaanoquantidade,
                vendaanovalor = iq.vendaanovalor
            from (
                select
                    elpv.codprodutovariacao,
                    sum(elpv.vendadiaquantidadeprevisao) as vendadiaquantidadeprevisao,
                    sum(elpv.estoqueminimo) as estoqueminimo,
                    sum(elpv.estoquemaximo) as estoquemaximo,
                    min(elpv.vendaultimocalculo) as vendaultimocalculo,
                    sum(elpv.vendabimestrequantidade) as vendabimestrequantidade,
                    sum(elpv.vendabimestrevalor) as vendabimestrevalor,
                    sum(elpv.vendasemestrequantidade) as vendasemestrequantidade,
                    sum(elpv.vendasemestrevalor) as vendasemestrevalor,
                    sum(elpv.vendaanoquantidade) as vendaanoquantidade,
                    sum(elpv.vendaanovalor) as vendaanovalor
                from tblestoquelocalprodutovariacao elpv
                $filtro
                group by elpv.codprodutovariacao
                ) iq
            where tblprodutovariacao.codprodutovariacao = iq.codprodutovariacao
        ";
        $atualizados = DB::update($sql);

        // Atualiza totais do Produto
        $filtro = '';
        if (!empty($codproduto_s)) {
            $filtro = implode(', ', $codproduto_s->toArray());
            $filtro = "where pv.codproduto in ($filtro)";
        }
        $sql = "
            update tblproduto
            set vendadiaquantidadeprevisao = iq.vendadiaquantidadeprevisao,
                estoqueminimo = iq.estoqueminimo,
                estoquemaximo = iq.estoquemaximo,
                vendaultimocalculo = iq.vendaultimocalculo,
                vendabimestrequantidade = iq.vendabimestrequantidade,
                vendabimestrevalor = iq.vendabimestrevalor,
                vendasemestrequantidade = iq.vendasemestrequantidade,
                vendasemestrevalor = iq.vendasemestrevalor,
                vendaanoquantidade = iq.vendaanoquantidade,
                vendaanovalor = iq.vendaanovalor,
                dataultimacompra = iq.dataultimacompra
            from (
                select
                    pv.codproduto,
                    sum(pv.vendadiaquantidadeprevisao) as vendadiaquantidadeprevisao,
                    sum(pv.estoqueminimo) as estoqueminimo,
                    sum(pv.estoquemaximo) as estoquemaximo,
                    min(pv.vendaultimocalculo) as vendaultimocalculo,
                    sum(pv.vendabimestrequantidade) as vendabimestrequantidade,
                    sum(pv.vendabimestrevalor) as vendabimestrevalor,
                    sum(pv.vendasemestrequantidade) as vendasemestrequantidade,
                    sum(pv.vendasemestrevalor) as vendasemestrevalor,
                    sum(pv.vendaanoquantidade) as vendaanoquantidade,
                    sum(pv.vendaanovalor) as vendaanovalor,
                    max(pv.dataultimacompra) as dataultimacompra
                from tblprodutovariacao pv
                $filtro
                group by pv.codproduto
                ) iq
            where tblproduto.codproduto = iq.codproduto
        ";
        $atualizados = DB::update($sql);

        // Atualiza totais da Marca
        $filtro = '';
        if (!empty($codmarca_s)) {
            $filtro = implode(', ', $codmarca_s->toArray());
            $filtro = "where p.codmarca in ($filtro)";
        }
        $sql = "
            update tblmarca
            set vendaultimocalculo = iq.vendaultimocalculo,
                vendabimestrevalor = iq.vendabimestrevalor,
                vendasemestrevalor = iq.vendasemestrevalor,
                vendaanovalor = iq.vendaanovalor,
                dataultimacompra = iq.dataultimacompra
            from (
                select
                    p.codmarca,
                    sum(p.vendadiaquantidadeprevisao) as vendadiaquantidadeprevisao,
                    sum(p.estoqueminimo) as estoqueminimo,
                    sum(p.estoquemaximo) as estoquemaximo,
                    min(p.vendaultimocalculo) as vendaultimocalculo,
                    sum(p.vendabimestrequantidade) as vendabimestrequantidade,
                    sum(p.vendabimestrevalor) as vendabimestrevalor,
                    sum(p.vendasemestrequantidade) as vendasemestrequantidade,
                    sum(p.vendasemestrevalor) as vendasemestrevalor,
                    sum(p.vendaanoquantidade) as vendaanoquantidade,
                    sum(p.vendaanovalor) as vendaanovalor,
                    max(p.dataultimacompra) as dataultimacompra
                from tblproduto p
                $filtro
                group by p.codmarca
                ) iq
            where tblmarca.codmarca = iq.codmarca
        ";
        $atualizados = DB::update($sql);

        // Calcula quantidade de Itens acima do maximo ou abaixo do minimo
        // baseado na soma do estoque de todos locais
        // e na soma das quantidades minimas e maximas de cada EstoqueLocalProdutoVariacao
        $sql = "
            update tblmarca
            set itensabaixominimo = iq3.itensabaixominimo
            , itensacimamaximo = iq3.itensacimamaximo
            from (
                select
                    iq2.codmarca,
                    sum(case when coalesce(iq2.saldoquantidade, 0) < coalesce(iq2.estoqueminimo, 0) then 1 else 0 end) as itensabaixominimo,
                    sum(case when coalesce(iq2.saldoquantidade, 0) > coalesce(iq2.estoquemaximo, 0) then 1 else 0 end) as itensacimamaximo
                from (
                    select
                        p.codmarca,
                        pv.estoqueminimo,
                        pv.estoquemaximo,
                        elpv.codprodutovariacao,
                        sum(coalesce(es.saldoquantidade, 0)) as saldoquantidade
                    from tblestoquelocalprodutovariacao elpv
                    inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = false)
                    inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
                    inner join tblproduto p on (p.codproduto = pv.codproduto)
                    $filtro
                    group by
                        p.codmarca,
                        pv.estoqueminimo,
                        pv.estoquemaximo,
                        elpv.codprodutovariacao
                    ) iq2
                group by iq2.codmarca
            ) iq3
            where iq3.codmarca = tblmarca.codmarca
            ";
        $atualizados = DB::update($sql);

        return true;

    }
}
