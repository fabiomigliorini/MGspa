<?php

namespace Mg\Dimp;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mpdf\Mpdf;

/**
 * Conciliação DIMP: por que o que a adquirente informou não bate com o que
 * a empresa emitiu em nota.
 *
 * A DIMP (Declaração de Informações de Meios de Pagamento) é o que as
 * adquirentes de cartão e os PSPs do PIX mandam para o fisco. O cruzamento
 * que o fisco faz é direto: "entrou este dinheiro no CNPJ, cadê a nota?".
 *
 * A venda de vale compras abre uma diferença legítima nesse cruzamento, e
 * abre por construção: o dinheiro do vale entra HOJE, com o cAut de verdade
 * na mão da adquirente, e o documento fiscal sai MESES DEPOIS, na retirada
 * do material, com tPag 12. Este relatório é a peça que explica essa
 * diferença -- e é por isso que ele vem logo depois do rateio do detPag, que
 * é quem a cria.
 *
 * O relatório tem duas partes. A primeira é narrativa: quanto entrou em
 * cartão/PIX nos negócios do mês, quanto as notas do mês declararam, a
 * diferença e o que a explica. A segunda são três CONFERÊNCIAS que têm que
 * fechar em zero -- e nenhuma delas é arredondamento: se aparecer linha em
 * qualquer uma, é defeito concreto, com nome e valor.
 *
 * O que NÃO dá para fazer aqui é somar as notas do mês e comparar com os
 * negócios do mês: o mesmo item pode estar em duas notas ativas (o cupom do
 * balcão e a NFe 55 do faturamento do crediário), e a soma dobraria. Por
 * isso a conferência do documento fiscal é feita nota a nota
 * (Σ vPag − vTroco = vNF), que é a conta que a SEFAZ faz.
 *
 * Só operação de SAÍDA entra: compra é dinheiro saindo, não é DIMP.
 *
 * Lê as DUAS estruturas de vale: `tblnegociovale` (o vale dentro do negócio)
 * e `tblvalecompra` (o vale do sistema antigo, que vendia fora do negócio) --
 * a segunda sai quando o milestone 9 converter o legado.
 */
class DimpConciliacaoService
{
    // tPag da NFe/NFC-e
    const TPAG_DINHEIRO = 1;
    const TPAG_CREDITO = 3;
    const TPAG_DEBITO = 4;
    const TPAG_VALE_PRESENTE = 12;
    const TPAG_PIX = 17;

    /** Cartão e PIX: é isso que a adquirente/PSP informa na DIMP. */
    const TPAG_ELETRONICOS = [self::TPAG_CREDITO, self::TPAG_DEBITO, self::TPAG_PIX];

    public static function pdf(array $filtros): string
    {
        $html = static::html($filtros);

        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        // Retrato: é um demonstrativo de conciliação, uma coluna de valores.
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 20,
            'margin_bottom' => 14,
            'margin_header' => 8,
            'margin_footer' => 5,
            'default_font' => 'helvetica',
            'tempDir' => $tempDir,
        ]);
        $mpdf->SetTitle('Conciliação DIMP');
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public static function html(array $filtros): string
    {
        $dados = static::apurar($filtros);
        return view('dimp.conciliacao', $dados)->render();
    }

    /**
     * Apura um mês fechado.
     *
     * $filtros: ano, mes e (opcional) codfilial.
     */
    public static function apurar(array $filtros): array
    {
        $ano = (int) ($filtros['ano'] ?? 0);
        $mes = (int) ($filtros['mes'] ?? 0);
        if ($ano < 2000 || $mes < 1 || $mes > 12) {
            abort(422, 'Informe o ano e o mês da conciliação.');
        }
        $codfilial = $filtros['codfilial'] ?? null;

        $inicio = Carbon::create($ano, $mes, 1)->startOfMonth();
        $fim = (clone $inicio)->endOfMonth();

        $bind = [
            'inicio' => $inicio->format('Y-m-d H:i:s'),
            'fim' => $fim->format('Y-m-d H:i:s'),
            'codfilial' => $codfilial,
        ];

        $negocios = static::negociosDoMes($bind);
        $pagamentos = static::pagamentosDoMes($bind);
        $valeNegocio = static::valeNoNegocio($bind);
        $valeLegado = static::valeLegado($bind);
        $notasDoMes = static::detPagDasNotasDoMes($bind);
        $semNota = static::negociosSemNota($bind);

        // Conferências que têm que fechar em zero. Nenhuma delas é
        // arredondamento: cada uma é um defeito concreto se aparecer.
        $conferencias = [
            static::confNotaPagamento($bind),
            static::confValeSemCredito($bind),
            static::confNegocioPagamento($bind),
        ];
        $fecha = true;
        foreach ($conferencias as $c) {
            if ($c['quantidade'] > 0) {
                $fecha = false;
            }
        }

        $eletronicoNegocio = static::somarEletronicos($pagamentos);
        $eletronicoNota = static::somarEletronicos($notasDoMes);
        $divergencia = round($eletronicoNegocio - $eletronicoNota, 2);
        $explicadoVale = round($valeNegocio['eletronico'] + $valeLegado['eletronico'], 2);

        return compact(
            'ano',
            'mes',
            'inicio',
            'fim',
            'codfilial',
            'negocios',
            'pagamentos',
            'valeNegocio',
            'valeLegado',
            'notasDoMes',
            'semNota',
            'conferencias',
            'fecha',
            'eletronicoNegocio',
            'eletronicoNota',
            'divergencia',
            'explicadoVale'
        );
    }

    public static function somarEletronicos($linhas)
    {
        $total = 0;
        foreach ($linhas as $l) {
            if (in_array((int) $l->tipo, static::TPAG_ELETRONICOS, true)) {
                $total += (float) $l->liquido;
            }
        }
        return round($total, 2);
    }

    /** Filtro de filial reusado por todas as consultas. */
    private static function filtroFilial($alias)
    {
        return "and (:codfilial::bigint is null or {$alias}.codfilial = :codfilial::bigint)";
    }

    private static function negociosDoMes(array $bind)
    {
        $sql = '
            select
                count(*) as quantidade,
                coalesce(sum(n.valortotal), 0) as valortotal,
                coalesce(sum(n.valorprodutos), 0) as valorprodutos,
                coalesce(sum(n.valorvales), 0) as valorvales
            from tblnegocio n
            where n.codnegociostatus = 2
              and n.codoperacao = 2
              and n.lancamento between :inicio and :fim
              ' . static::filtroFilial('n') . '
        ';
        return (array) DB::select($sql, $bind)[0];
    }

    /** Como o cliente pagou os negócios do mês, por tPag. */
    private static function pagamentosDoMes(array $bind)
    {
        $sql = '
            select
                coalesce(nfp.tipo, 99) as tipo,
                count(*) as quantidade,
                coalesce(sum(nfp.valortotal), 0) as bruto,
                coalesce(sum(nfp.valortroco), 0) as troco,
                coalesce(sum(nfp.valortotal - coalesce(nfp.valortroco, 0)), 0) as liquido
            from tblnegocioformapagamento nfp
            inner join tblnegocio n on (n.codnegocio = nfp.codnegocio)
            where n.codnegociostatus = 2
              and n.codoperacao = 2
              and n.lancamento between :inicio and :fim
              ' . static::filtroFilial('n') . '
            group by coalesce(nfp.tipo, 99)
            order by 1
        ';
        return DB::select($sql, $bind);
    }

    /**
     * Vale vendido DENTRO do negócio no mês.
     *
     * "cobrado" é o que o cliente pagou pelo vale: a fatia paga
     * (valortotal, já com o rateio de desconto) mais a fatia de juros que
     * coube a ele. É exatamente o valor que o rateio do detPag tira dos
     * pagamentos da nota.
     *
     * "eletronico" é quanto disso entrou por cartão/PIX, rateado na mesma
     * proporção em que o vale participa do negócio -- o cartão é passado
     * pelo negócio inteiro, não pelo vale.
     */
    private static function valeNoNegocio(array $bind)
    {
        $sql = '
            with vale as (
                select
                    nv.codnegocio,
                    sum(nv.valorvale) as face,
                    sum(nv.valortotal) as pago
                from tblnegociovale nv
                inner join tblnegocio n on (n.codnegocio = nv.codnegocio)
                where nv.inativo is null
                  and n.codnegociostatus = 2
                  and n.codoperacao = 2
                  and n.lancamento between :inicio and :fim
                  ' . static::filtroFilial('n') . '
                group by nv.codnegocio
            ),
            juros as (
                select
                    v.codnegocio,
                    v.face,
                    v.pago,
                    case
                        when coalesce(n.valorprodutos, 0) + coalesce(n.valorvales, 0) > 0
                        then round(coalesce(n.valorjuros, 0)
                             * coalesce(n.valorvales, 0)
                             / (coalesce(n.valorprodutos, 0) + coalesce(n.valorvales, 0)), 2)
                        else 0
                    end as jurosvale,
                    n.valortotal as totalnegocio
                from vale v
                inner join tblnegocio n on (n.codnegocio = v.codnegocio)
            ),
            eletronico as (
                select
                    nfp.codnegocio,
                    sum(nfp.valortotal - coalesce(nfp.valortroco, 0)) as liquido
                from tblnegocioformapagamento nfp
                where nfp.codnegocio in (select codnegocio from vale)
                  and nfp.tipo in (' . implode(',', static::TPAG_ELETRONICOS) . ')
                group by nfp.codnegocio
            )
            select
                count(*) as quantidade,
                coalesce(sum(j.face), 0) as face,
                coalesce(sum(j.pago + j.jurosvale), 0) as cobrado,
                coalesce(sum(
                    case
                        when j.totalnegocio > 0
                        then round(coalesce(e.liquido, 0) * (j.pago + j.jurosvale) / j.totalnegocio, 2)
                        else 0
                    end
                ), 0) as eletronico
            from juros j
            left join eletronico e on (e.codnegocio = j.codnegocio)
        ';
        return (array) DB::select($sql, $bind)[0];
    }

    /**
     * Vale vendido no SISTEMA ANTIGO (tblvalecompra), que vendia fora do
     * negócio: entrou dinheiro no mês sem negócio e sem nota nenhuma.
     *
     * Sai do relatório quando o legado for convertido. Enquanto a tabela
     * existir, ela precisa aparecer aqui, ou o mês em que o MGLara ainda
     * vendia vale não fecha.
     */
    private static function valeLegado(array $bind)
    {
        if (!static::tabelaExiste('tblvalecompra')) {
            return ['quantidade' => 0, 'total' => 0, 'eletronico' => 0];
        }
        $sql = '
            with eletronico as (
                select f.codvalecompra, sum(f.valorpagamento) as valor
                from tblvalecompraformapagamento f
                inner join tblformapagamento fp on (fp.codformapagamento = f.codformapagamento)
                where fp.pix = true or fp.formapagamento ilike \'%cart%\'
                group by f.codvalecompra
            )
            select
                count(*) as quantidade,
                coalesce(sum(vc.total), 0) as total,
                coalesce(sum(coalesce(e.valor, 0)), 0) as eletronico
            from tblvalecompra vc
            left join eletronico e on (e.codvalecompra = vc.codvalecompra)
            where vc.inativo is null
              and vc.criacao between :inicio and :fim
              ' . static::filtroFilial('vc') . '
        ';
        return (array) DB::select($sql, $bind)[0];
    }

    /** O que as notas EMITIDAS no mês declararam, por tPag. */
    private static function detPagDasNotasDoMes(array $bind)
    {
        $sql = '
            select
                coalesce(nfp.tipo, 99) as tipo,
                count(*) as quantidade,
                coalesce(sum(nfp.valorpagamento), 0) as bruto,
                coalesce(sum(nfp.troco), 0) as troco,
                coalesce(sum(nfp.valorpagamento - coalesce(nfp.troco, 0)), 0) as liquido
            from tblnotafiscalpagamento nfp
            inner join tblnotafiscal nf on (nf.codnotafiscal = nfp.codnotafiscal)
            where nf.emissao between :inicio and :fim
              and nf.codoperacao = 2
              and nf.nfecancelamento is null
              and nf.nfeinutilizacao is null
              ' . static::filtroFilial('nf') . '
            group by coalesce(nfp.tipo, 99)
            order by 1
        ';
        return DB::select($sql, $bind);
    }

    /** Negócios do mês que não geraram nota nenhuma. */
    private static function negociosSemNota(array $bind)
    {
        $sql = '
            with comnota as (
                select distinct n.codnegocio
                from tblnegocio n
                inner join tblnegocioprodutobarra npb on (npb.codnegocio = n.codnegocio)
                inner join tblnotafiscalprodutobarra nfpb on (nfpb.codnegocioprodutobarra = npb.codnegocioprodutobarra)
                inner join tblnotafiscal nf on (nf.codnotafiscal = nfpb.codnotafiscal)
                where n.codnegociostatus = 2
                  and n.codoperacao = 2
                  and n.lancamento between :inicio and :fim
                  and nf.nfecancelamento is null
                  and nf.nfeinutilizacao is null
                  ' . static::filtroFilial('n') . '
            )
            select
                count(*) as quantidade,
                coalesce(sum(n.valortotal), 0) as valortotal
            from tblnegocio n
            where n.codnegociostatus = 2
              and n.codoperacao = 2
              and n.lancamento between :inicio and :fim
              and n.codnegocio not in (select codnegocio from comnota)
              ' . static::filtroFilial('n') . '
        ';
        return (array) DB::select($sql, $bind)[0];
    }

    // ---------------------------------------------------------------
    // CONFERENCIAS -- cada uma tem que dar ZERO. Nenhuma e arredondamento.
    // ---------------------------------------------------------------

    /**
     * A conferencia que a SEFAZ faz: soma dos vPag menos o vTroco tem que
     * dar exatamente o vNF. E' exatamente isso que o rateio do detPag com
     * vale precisa manter verdadeiro -- se uma nota aparecer aqui, ela seria
     * rejeitada na transmissao.
     */
    private static function confNotaPagamento(array $bind)
    {
        $sql = '
            with pag as (
                select nfp.codnotafiscal,
                       sum(nfp.valorpagamento - coalesce(nfp.troco, 0)) as liquido
                from tblnotafiscalpagamento nfp
                group by nfp.codnotafiscal
            )
            select count(*) as quantidade,
                   coalesce(sum(abs(nf.valortotal - p.liquido)), 0) as valor
            from tblnotafiscal nf
            inner join pag p on (p.codnotafiscal = nf.codnotafiscal)
            where nf.emissao between :inicio and :fim
              and nf.codoperacao = 2
              and nf.nfecancelamento is null
              and nf.nfeinutilizacao is null
              and abs(nf.valortotal - p.liquido) >= 0.005
              ' . static::filtroFilial('nf') . '
        ';
        $r = (array) DB::select($sql, $bind)[0];
        $r['titulo'] = 'Notas do mês em que a soma dos pagamentos não bate com o total da nota';
        $r['nota'] = 'É a conferência que a SEFAZ faz (Σ vPag − vTroco = vNF). Nota que apareça aqui seria rejeitada na transmissão.';
        return $r;
    }

    /** Negócio fechado com vale e sem o crédito emitido: vale que nao existe. */
    private static function confValeSemCredito(array $bind)
    {
        $sql = '
            select count(*) as quantidade, coalesce(sum(nv.valorvale), 0) as valor
            from tblnegociovale nv
            inner join tblnegocio n on (n.codnegocio = nv.codnegocio)
            where nv.inativo is null
              and nv.codtitulo is null
              and n.codnegociostatus = 2
              and n.codoperacao = 2
              and n.lancamento between :inicio and :fim
              ' . static::filtroFilial('n') . '
        ';
        $r = (array) DB::select($sql, $bind)[0];
        $r['titulo'] = 'Vales vendidos em negócio fechado sem o crédito emitido';
        $r['nota'] = 'O cliente pagou pelo vale e não existe título de crédito do outro lado. Dinheiro recebido sem vale.';
        return $r;
    }

    /** Negocio com financeiro cujo total nao bate com os pagamentos. */
    private static function confNegocioPagamento(array $bind)
    {
        $sql = '
            with pag as (
                select nfp.codnegocio,
                       sum(nfp.valortotal - coalesce(nfp.valortroco, 0)) as liquido
                from tblnegocioformapagamento nfp
                group by nfp.codnegocio
            )
            select count(*) as quantidade,
                   coalesce(sum(abs(n.valortotal - coalesce(p.liquido, 0))), 0) as valor
            from tblnegocio n
            inner join tblnaturezaoperacao nat on (nat.codnaturezaoperacao = n.codnaturezaoperacao)
            left join pag p on (p.codnegocio = n.codnegocio)
            where n.codnegociostatus = 2
              and n.codoperacao = 2
              and n.codpdv is not null
              and nat.financeiro = true
              and n.lancamento between :inicio and :fim
              and abs(n.valortotal - coalesce(p.liquido, 0)) >= 0.005
              ' . static::filtroFilial('n') . '
        ';
        $r = (array) DB::select($sql, $bind)[0];
        $r['titulo'] = 'Negócios do mês em que os pagamentos não cobrem o total cobrado';
        $r['nota'] = 'É a conferência que o fechamento do negócio faz, nas vendas do PDV. Se aparecer aqui, o caixa do dia não fecha.';
        return $r;
    }

    private static function tabelaExiste($tabela)
    {
        return count(DB::select('select to_regclass(?) as t', [$tabela])) > 0
            && DB::select('select to_regclass(?) as t', [$tabela])[0]->t !== null;
    }
}
