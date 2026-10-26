<?php

namespace Mg\Estoque;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Mpdf\Mpdf;

/**
 * Relatórios de estoque-saldo (PDF via mPDF). Porta os 3 relatórios do
 * legado MGLara: comparativo de vendas, físico × fiscal e transferências.
 */
class EstoqueSaldoRelatorioService
{
    private static function pdf(string $titulo, string $corpoHtml): string
    {
        ini_set('memory_limit', '1024M');
        ini_set('pcre.backtrack_limit', '100000000');
        ini_set('pcre.recursion_limit', '100000000');
        set_time_limit(300);

        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $css = '
            body { font-family: helvetica; font-size: 8pt; color: #222; }
            h1 { font-size: 13pt; margin: 0 0 2px 0; }
            .sub { color: #666; font-size: 8pt; margin-bottom: 8px; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #eee; text-align: left; padding: 3px 4px; border-bottom: 1px solid #999; font-size: 7.5pt; }
            td { padding: 2px 4px; border-bottom: 1px solid #eee; font-size: 7.5pt; }
            .r { text-align: right; }
            .c { text-align: center; }
            .grupo { background: #f3f3f3; font-weight: bold; }
            .neg { color: #c62828; }
        ';

        $html = "<html><head><style>{$css}</style></head><body>{$corpoHtml}</body></html>";

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 12,
            'margin_bottom' => 10,
            'default_font' => 'helvetica',
            'tempDir' => $tempDir,
        ]);
        $mpdf->SetTitle($titulo);
        $mpdf->WriteHTML($html);
        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    private static function num($v, int $dec = 0): string
    {
        return number_format((float) $v, $dec, ',', '.');
    }

    private static function moeda($v): string
    {
        return 'R$ ' . number_format((float) $v, 2, ',', '.');
    }

    // ─────────────────────── Comparativo de Vendas ────────────────────────

    public static function comparativoVendas(array $f): string
    {
        $deposito = (int) ($f['codestoquelocaldeposito'] ?? 101001);
        $filial = (int) ($f['codestoquelocalfilial'] ?? 0);
        $dias = (int) ($f['dias_previsao'] ?? 15);
        $di = Carbon::parse($f['datainicial'] ?? Carbon::now()->subDays(15))->format('Y-m-d H:i');
        $df = Carbon::parse($f['datafinal'] ?? Carbon::yesterday()->endOfDay())->format('Y-m-d H:i');

        $where = [];
        if (!empty($f['codmarca'])) {
            $where[] = 'm.codmarca = ' . (int) $f['codmarca'];
        }
        if (isset($f['marcacontrolada']) && in_array((int) $f['marcacontrolada'], [1, 2])) {
            $where[] = 'm.controlada = ' . ((int) $f['marcacontrolada'] === 1 ? 'true' : 'false');
        }
        if (!empty($f['saldo_deposito'])) {
            $where[] = (int) $f['saldo_deposito'] === 1
                ? 'es_deposito.saldoquantidade > 0'
                : '(coalesce(es_deposito.saldoquantidade,0) <= 0)';
        }
        if (!empty($f['saldo_filial'])) {
            $where[] = (int) $f['saldo_filial'] === 1
                ? 'es_filial.saldoquantidade > coalesce(elpv_filial.vendadiaquantidadeprevisao * ' . $dias . ', 0)'
                : 'coalesce(es_filial.saldoquantidade,0) <= coalesce(elpv_filial.vendadiaquantidadeprevisao * ' . $dias . ', 0)';
        }
        if (!empty($f['minimo'])) {
            $where[] = (int) $f['minimo'] === 1
                ? 'es_filial.saldoquantidade > coalesce(elpv_filial.estoqueminimo,0)'
                : 'coalesce(es_filial.saldoquantidade,0) <= coalesce(elpv_filial.estoqueminimo,0)';
        }
        if (!empty($f['maximo'])) {
            $where[] = (int) $f['maximo'] === 1
                ? 'es_filial.saldoquantidade > coalesce(elpv_filial.estoquemaximo,0)'
                : 'coalesce(es_filial.saldoquantidade,0) <= coalesce(elpv_filial.estoquemaximo,0)';
        }
        $whereSql = empty($where) ? '' : 'where ' . implode(' and ', $where);

        $sql = "
            select m.marca, coalesce(pv.referencia, p.referencia) as referencia,
                p.codproduto, p.produto, pv.variacao, iq.quantidade_vendida,
                es_filial.saldoquantidade as saldo_filial,
                elpv_filial.estoqueminimo, elpv_filial.estoquemaximo,
                es_deposito.saldoquantidade as saldo_deposito,
                elpv_deposito.corredor, elpv_deposito.prateleira, elpv_deposito.coluna, elpv_deposito.bloco,
                elpv_filial.vendadiaquantidadeprevisao * {$dias} as previsao_vendas
            from (
                select iq_pb.codprodutovariacao,
                    sum(iq_npb.quantidade * coalesce(iq_pe.quantidade,1)) as quantidade_vendida
                from tblnegocio iq_n
                inner join tblnaturezaoperacao iq_no on (iq_no.codnaturezaoperacao = iq_n.codnaturezaoperacao)
                inner join tblnegocioprodutobarra iq_npb on (iq_npb.codnegocio = iq_n.codnegocio)
                inner join tblprodutobarra iq_pb on (iq_pb.codprodutobarra = iq_npb.codprodutobarra)
                inner join tblproduto iq_p on (iq_p.codproduto = iq_pb.codproduto)
                inner join tbltipoproduto iq_tp on (iq_tp.codtipoproduto = iq_p.codtipoproduto)
                left join tblprodutoembalagem iq_pe on (iq_pe.codprodutoembalagem = iq_pb.codprodutoembalagem)
                where iq_n.codnegociostatus = 2
                and iq_n.codestoquelocal = {$filial}
                and iq_n.lancamento between '{$di}' and '{$df}'
                and iq_no.venda = true and iq_no.estoque = true and iq_tp.estoque = true
                group by iq_pb.codprodutovariacao
            ) iq
            left join tblprodutovariacao pv on (pv.codprodutovariacao = iq.codprodutovariacao)
            left join tblestoquelocalprodutovariacao elpv_deposito on (elpv_deposito.codprodutovariacao = iq.codprodutovariacao and elpv_deposito.codestoquelocal = {$deposito})
            left join tblestoquesaldo es_deposito on (es_deposito.codestoquelocalprodutovariacao = elpv_deposito.codestoquelocalprodutovariacao and es_deposito.fiscal = false)
            left join tblestoquelocalprodutovariacao elpv_filial on (elpv_filial.codprodutovariacao = iq.codprodutovariacao and elpv_filial.codestoquelocal = {$filial})
            left join tblestoquesaldo es_filial on (es_filial.codestoquelocalprodutovariacao = elpv_filial.codestoquelocalprodutovariacao and es_filial.fiscal = false)
            left join tblproduto p on (p.codproduto = pv.codproduto)
            left join tblmarca m on (m.codmarca = coalesce(pv.codmarca, p.codmarca))
            {$whereSql}
            order by m.marca, p.produto, pv.variacao nulls first
        ";

        $rows = DB::select($sql);

        $linhas = '';
        foreach ($rows as $r) {
            $loc = implode('-', array_filter([$r->corredor, $r->prateleira, $r->coluna, $r->bloco]));
            $linhas .= '<tr>'
                . '<td>' . htmlspecialchars($r->marca ?? '') . '</td>'
                . '<td class="r">' . str_pad($r->codproduto, 6, '0', STR_PAD_LEFT) . '</td>'
                . '<td>' . htmlspecialchars($r->produto ?? '') . ' ' . htmlspecialchars($r->variacao ?? '') . '</td>'
                . '<td>' . htmlspecialchars($r->referencia ?? '') . '</td>'
                . '<td class="r">' . self::num($r->quantidade_vendida) . '</td>'
                . '<td class="r">' . self::num($r->previsao_vendas) . '</td>'
                . '<td class="r">' . self::num($r->saldo_filial) . '</td>'
                . '<td class="r">' . self::num($r->estoqueminimo) . '/' . self::num($r->estoquemaximo) . '</td>'
                . '<td class="r">' . self::num($r->saldo_deposito) . '</td>'
                . '<td>' . htmlspecialchars($loc) . '</td>'
                . '</tr>';
        }

        $corpo = '<h1>Comparativo de Vendas — Depósito × Filial</h1>'
            . '<div class="sub">Período ' . Carbon::parse($di)->format('d/m/Y') . ' a ' . Carbon::parse($df)->format('d/m/Y')
            . ' · previsão ' . $dias . ' dias · ' . count($rows) . ' itens</div>'
            . '<table><thead><tr>'
            . '<th>Marca</th><th class="r">Código</th><th>Produto</th><th>Ref.</th>'
            . '<th class="r">Vendas</th><th class="r">Prev.</th><th class="r">Sld Filial</th>'
            . '<th class="r">Mín/Máx</th><th class="r">Disp Dep.</th><th>Localização</th>'
            . '</tr></thead><tbody>' . $linhas . '</tbody></table>';

        return self::pdf('Comparativo de Vendas', $corpo);
    }

    // ───────────────────────── Físico × Fiscal ────────────────────────────

    public static function fisicoFiscal(array $f): string
    {
        $mes = (int) ($f['mes'] ?? date('m'));
        $ano = (int) ($f['ano'] ?? date('Y'));
        $codempresa = (int) ($f['codempresa'] ?? 0);
        $ultimoDia = Carbon::createFromDate($ano, $mes, 1)->endOfMonth()->format('Y-m-d');
        $localFiltro = !empty($f['codestoquelocal']) ? ' and el.codestoquelocal = ' . (int) $f['codestoquelocal'] : '';

        $subSaldo = function ($fiscal) use ($codempresa, $ultimoDia, $localFiltro) {
            $fb = $fiscal ? 'true' : 'false';
            return "
                select pv.codproduto,
                    sum(em.saldoquantidade) as saldoquantidade,
                    sum(em.saldovalor) as saldovalor,
                    avg(em.customedio) as customedio
                from tblestoquelocalprodutovariacao elpv
                inner join tblprodutovariacao pv on (pv.codprodutovariacao = elpv.codprodutovariacao)
                inner join tblestoquelocal el on (el.codestoquelocal = elpv.codestoquelocal)
                inner join tblfilial f on (f.codfilial = el.codfilial)
                inner join tblestoquesaldo es on (es.codestoquelocalprodutovariacao = elpv.codestoquelocalprodutovariacao and es.fiscal = {$fb})
                inner join tblestoquemes em on (em.codestoquemes = (select em2.codestoquemes from tblestoquemes em2 where em2.codestoquesaldo = es.codestoquesaldo and em2.mes <= '{$ultimoDia}' order by mes desc limit 1))
                where f.codempresa = {$codempresa} {$localFiltro}
                group by pv.codproduto
            ";
        };

        $where = ['p.codtipoproduto = 0'];
        if (!empty($f['codmarca'])) {
            $where[] = 'm.codmarca = ' . (int) $f['codmarca'];
        }
        if (!empty($f['codncm'])) {
            $where[] = 'p.codncm = ' . (int) $f['codncm'];
        }
        if (!empty($f['codtributacao'])) {
            $where[] = 'p.codtributacao = ' . (int) $f['codtributacao'];
        }
        if (!empty($f['preco_de'])) {
            $where[] = 'p.preco >= ' . (float) $f['preco_de'];
        }
        if (!empty($f['preco_ate'])) {
            $where[] = 'p.preco <= ' . (float) $f['preco_ate'];
        }
        if (!empty($f['codsubgrupoproduto'])) {
            $where[] = 'p.codsubgrupoproduto = ' . (int) $f['codsubgrupoproduto'];
        } elseif (!empty($f['codgrupoproduto'])) {
            $where[] = 'sgp.codgrupoproduto = ' . (int) $f['codgrupoproduto'];
        } elseif (!empty($f['codfamiliaproduto'])) {
            $where[] = 'gp.codfamiliaproduto = ' . (int) $f['codfamiliaproduto'];
        } elseif (!empty($f['codsecaoproduto'])) {
            $where[] = 'fp.codsecaoproduto = ' . (int) $f['codsecaoproduto'];
        }
        if (!empty($f['produto'])) {
            foreach (preg_split('/\s+/', trim($f['produto'])) as $palavra) {
                $where[] = "p.produto ilike '%" . str_replace("'", "''", $palavra) . "%'";
            }
        }
        if (!empty($f['saldo_fisico'])) {
            $where[] = self::condSaldo('fisico.saldoquantidade', (int) $f['saldo_fisico']);
        }
        if (!empty($f['saldo_fiscal'])) {
            $where[] = self::condSaldo('fiscal.saldoquantidade', (int) $f['saldo_fiscal']);
        }
        if (!empty($f['saldo_fisico_fiscal'])) {
            $where[] = (int) $f['saldo_fisico_fiscal'] === -1
                ? 'coalesce(fiscal.saldoquantidade,0) < coalesce(fisico.saldoquantidade,0)'
                : 'coalesce(fiscal.saldoquantidade,0) > coalesce(fisico.saldoquantidade,0)';
        }

        $sql = "
            select p.codproduto, p.produto, p.preco,
                fisico.saldoquantidade as fisico_qtd, fisico.saldovalor as fisico_val, fisico.customedio as fisico_custo,
                fiscal.saldoquantidade as fiscal_qtd, fiscal.saldovalor as fiscal_val, fiscal.customedio as fiscal_custo,
                m.marca
            from tblproduto p
            left join tblmarca m on (m.codmarca = p.codmarca)
            left join tblsubgrupoproduto sgp on (sgp.codsubgrupoproduto = p.codsubgrupoproduto)
            left join tblgrupoproduto gp on (gp.codgrupoproduto = sgp.codgrupoproduto)
            left join tblfamiliaproduto fp on (fp.codfamiliaproduto = gp.codfamiliaproduto)
            left join (" . $subSaldo(false) . ") fisico on (fisico.codproduto = p.codproduto)
            left join (" . $subSaldo(true) . ") fiscal on (fiscal.codproduto = p.codproduto)
            where " . implode(' and ', $where) . "
            order by p.produto, p.codproduto
        ";

        $rows = DB::select($sql);

        $linhas = '';
        foreach ($rows as $r) {
            $dif = (float) ($r->fiscal_qtd ?? 0) != (float) ($r->fisico_qtd ?? 0);
            $linhas .= '<tr' . ($dif ? ' class="neg"' : '') . '>'
                . '<td class="r">' . str_pad($r->codproduto, 6, '0', STR_PAD_LEFT) . '</td>'
                . '<td>' . htmlspecialchars($r->produto ?? '') . '</td>'
                . '<td class="r">' . self::moeda($r->preco) . '</td>'
                . '<td class="r">' . self::num($r->fisico_qtd) . '</td>'
                . '<td class="r">' . self::moeda($r->fisico_custo) . '</td>'
                . '<td class="r">' . self::moeda($r->fisico_val) . '</td>'
                . '<td class="r">' . self::num($r->fiscal_qtd) . '</td>'
                . '<td class="r">' . self::moeda($r->fiscal_custo) . '</td>'
                . '<td class="r">' . self::moeda($r->fiscal_val) . '</td>'
                . '<td>' . htmlspecialchars($r->marca ?? '') . '</td>'
                . '</tr>';
        }

        $corpo = '<h1>Físico × Fiscal</h1>'
            . '<div class="sub">' . str_pad($mes, 2, '0', STR_PAD_LEFT) . '/' . $ano . ' · ' . count($rows) . ' itens · linhas em vermelho = divergência</div>'
            . '<table><thead><tr>'
            . '<th class="r">Código</th><th>Produto</th><th class="r">Venda</th>'
            . '<th class="r">Fís Qtd</th><th class="r">Fís Médio</th><th class="r">Fís Valor</th>'
            . '<th class="r">Fisc Qtd</th><th class="r">Fisc Médio</th><th class="r">Fisc Valor</th><th>Marca</th>'
            . '</tr></thead><tbody>' . $linhas . '</tbody></table>';

        return self::pdf('Físico x Fiscal', $corpo);
    }

    private static function condSaldo(string $col, int $valor): string
    {
        if ($valor === -1) {
            return "coalesce({$col},0) < 0";
        }
        if ($valor === 1) {
            return "coalesce({$col},0) > 0";
        }
        return "coalesce({$col},0) = 0";
    }

    // ───────────────────────── Transferências ─────────────────────────────

    public static function transferencias(array $f): string
    {
        $origem = (int) ($f['codestoquelocalorigem'] ?? 0);
        $destino = (int) ($f['codestoquelocaldestino'] ?? 0);

        $where = [
            'coalesce(sld_dest.saldoquantidade,0) <= coalesce(elpv_dest.estoqueminimo,0)',
            'coalesce(elpv_dest.estoquemaximo,0) > 1',
            'coalesce(sld_orig.saldoquantidade,0) > 0',
        ];
        if (!empty($f['codmarca'])) {
            $where[] = 'p.codmarca = ' . (int) $f['codmarca'];
        }
        if (!empty($f['abc'])) {
            $where[] = $f['abc'] === 'AB'
                ? "p.abc in ('A','B')"
                : "p.abc = '" . preg_replace('/[^A-D]/', '', $f['abc']) . "'";
        }

        $sql = "
            with itens as (
                select m.marca, p.codproduto, p.produto, pv.codprodutovariacao, pv.variacao,
                    coalesce(pv.referencia, p.referencia) as referencia,
                    elpv_dest.estoqueminimo, elpv_dest.estoquemaximo,
                    sld_dest.saldoquantidade as saldodestino,
                    sld_orig.saldoquantidade as saldoorigem,
                    coalesce(pe.quantidade, 1) as quantidadeembalagem
                from tblproduto p
                inner join tblmarca m on (m.codmarca = p.codmarca)
                inner join tblprodutovariacao pv on (pv.codproduto = p.codproduto)
                left join tblprodutoembalagem pe on (pe.codprodutoembalagem = p.codprodutoembalagemtransferencia)
                inner join tblestoquelocalprodutovariacao elpv_orig on (elpv_orig.codprodutovariacao = pv.codprodutovariacao and elpv_orig.codestoquelocal = {$origem})
                inner join tblestoquelocalprodutovariacao elpv_dest on (elpv_dest.codprodutovariacao = pv.codprodutovariacao and elpv_dest.codestoquelocal = {$destino})
                left join tblestoquesaldo sld_orig on (sld_orig.codestoquelocalprodutovariacao = elpv_orig.codestoquelocalprodutovariacao and sld_orig.fiscal = false)
                left join tblestoquesaldo sld_dest on (sld_dest.codestoquelocalprodutovariacao = elpv_dest.codestoquelocalprodutovariacao and sld_dest.fiscal = false)
                where " . implode(' and ', $where) . "
            )
            select itens.*,
                ceil((coalesce(estoquemaximo,0) - coalesce(saldodestino,0)) / quantidadeembalagem) * quantidadeembalagem as transferir
            from itens
            order by itens.marca, itens.produto, itens.variacao
        ";

        $rows = DB::select($sql);

        $linhas = '';
        foreach ($rows as $r) {
            $linhas .= '<tr>'
                . '<td>' . htmlspecialchars($r->marca ?? '') . '</td>'
                . '<td class="r">' . str_pad($r->codproduto, 6, '0', STR_PAD_LEFT) . '</td>'
                . '<td>' . htmlspecialchars($r->produto ?? '') . ' ' . htmlspecialchars($r->variacao ?? '') . '</td>'
                . '<td>' . htmlspecialchars($r->referencia ?? '') . '</td>'
                . '<td class="r">' . self::num($r->estoqueminimo) . '/' . self::num($r->estoquemaximo) . '</td>'
                . '<td class="r">' . self::num($r->saldodestino) . '</td>'
                . '<td class="r">' . self::num($r->saldoorigem) . '</td>'
                . '<td class="r">' . self::num($r->quantidadeembalagem) . '</td>'
                . '<td class="r"><b>' . self::num($r->transferir) . '</b></td>'
                . '</tr>';
        }

        $corpo = '<h1>Sugestão de Transferências</h1>'
            . '<div class="sub">Origem ' . $origem . ' → Destino ' . $destino . ' · ' . count($rows) . ' itens</div>'
            . '<table><thead><tr>'
            . '<th>Marca</th><th class="r">Código</th><th>Produto</th><th>Ref.</th>'
            . '<th class="r">Mín/Máx</th><th class="r">Sld Destino</th><th class="r">Sld Origem</th>'
            . '<th class="r">Emb.</th><th class="r">Transferir</th>'
            . '</tr></thead><tbody>' . $linhas . '</tbody></table>';

        return self::pdf('Transferências', $corpo);
    }
}
