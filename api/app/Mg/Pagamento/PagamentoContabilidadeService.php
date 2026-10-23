<?php

namespace Mg\Pagamento;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mg\Filial\Filial;
use Mg\Portador\Portador;
use Mpdf\Mpdf;

// Titulos pagos no periodo, para a contabilidade (TASK-256): os pagamentos de
// titulo efetivados em que saiu dinheiro, encontro de contas e folha, ou que
// mexeram em titulo a pagar; cada um com as linhas ativas dos titulos
// (principal, juros, multa, desconto e total), por fiscal/gerencial, filial e
// portador. Filial e gerencial sao do titulo: o pagamento que tem titulos fora
// do filtro aparece com o valor cheio e so os titulos do filtro.
class PagamentoContabilidadeService
{
    // linhas de titulo
    const LIMITE = 10000;

    const VALORES = ['principal', 'juros', 'multa', 'desconto', 'total'];

    public static function pdf(array $filtros): string
    {
        $html = self::html($filtros);
        // o mes de todas as filiais passa do limite padrao do pcre no WriteHTML
        ini_set('pcre.backtrack_limit', '100000000');

        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 22,
            'margin_bottom' => 14,
            'margin_header' => 5,
            'margin_footer' => 5,
            'default_font' => 'dejavusanscondensed',
            'tempDir' => $tempDir,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public static function html(array $filtros): string
    {
        ini_set('memory_limit', '512M');
        set_time_limit(120);

        $linhas = static::linhas($filtros);
        if (count($linhas) > static::LIMITE) {
            abort(422, 'Mais de ' . static::LIMITE . ' títulos: refine os filtros do relatório.');
        }
        $relatorio = static::agrupar($linhas);

        $de = Carbon::parse($filtros['transacao_de']);
        $ate = Carbon::parse($filtros['transacao_ate']);
        $filtro = [];
        if (isset($filtros['gerencial'])) {
            $filtro[] = $filtros['gerencial'] ? 'Gerencial' : 'Fiscal';
        }
        if (!empty($filtros['codfilial'])) {
            $filtro[] = optional(Filial::find($filtros['codfilial']))->filial;
        }
        if (!empty($filtros['codportador'])) {
            $filtro[] = optional(Portador::find($filtros['codportador']))->portador;
        }

        return view('pagamento.contabilidade', [
            'grupos' => $relatorio['grupos'],
            'totais' => $relatorio['totais'],
            'periodo' => $de->format('d/m/Y') . ' a ' . $ate->format('d/m/Y'),
            'filtro' => implode(' · ', array_filter($filtro)),
        ])->render();
    }

    public static function linhas(array $filtros): array
    {
        // a linha que vale: nem estorno nem estornada (PagamentoTituloService::baixas)
        $ativa = fn($a) => "{$a}.codtipomovimentotitulo < 900
            and {$a}.codmovimentotituloestorno is null
            and not exists (select 1 from tblmovimentotitulo e where e.codmovimentotituloestorno = {$a}.codmovimentotitulo)";

        $params = [
            'de' => Carbon::parse($filtros['transacao_de'])->startOfDay()->format('Y-m-d H:i:s'),
            'ate' => Carbon::parse($filtros['transacao_ate'])->endOfDay()->format('Y-m-d H:i:s'),
        ];
        $where = '';
        if (!empty($filtros['codfilial'])) {
            $where .= ' and t.codfilial = :codfilial';
            $params['codfilial'] = (int) $filtros['codfilial'];
        }
        if (isset($filtros['gerencial'])) {
            $where .= ' and t.gerencial = :gerencial';
            $params['gerencial'] = $filtros['gerencial'] ? 'true' : 'false';
        }
        if (!empty($filtros['codportador'])) {
            $where .= ' and coalesce(p.codportadordestino, p.codportadororigem) = :codportador';
            $params['codportador'] = (int) $filtros['codportador'];
        }
        $compensacao = PagamentoService::MEIO_COMPENSACAO;
        $folha = PagamentoService::MEIO_FOLHA;
        $limite = static::LIMITE + 1;

        return DB::select("
            select p.codpagamento, p.transacao, p.meio, p.total as pagamentototal,
                p.codportadororigem, p.codportadordestino, p.observacoes,
                po.portador, pp.pessoa as pagamentopessoa,
                (select count(*) from tblmovimentotitulo x
                    where x.codpagamento = p.codpagamento and {$ativa('x')}) as linhaspagamento,
                mt.codmovimentotitulo, mt.codtipomovimentotitulo, tmt.tipomovimentotitulo,
                mt.principal, mt.juros, mt.multa, mt.desconto, mt.total,
                t.codtitulo, t.numero, t.fatura, t.vencimento, t.gerencial, t.codfilial,
                f.filial, pe.pessoa, cc.contacontabil, tt.tipotitulo
            from tblpagamento p
            inner join tblmovimentotitulo mt on (mt.codpagamento = p.codpagamento)
            inner join tbltitulo t on (t.codtitulo = mt.codtitulo)
            inner join tbltipotitulo tt on (tt.codtipotitulo = t.codtipotitulo)
            inner join tbltipomovimentotitulo tmt on (tmt.codtipomovimentotitulo = mt.codtipomovimentotitulo)
            inner join tblfilial f on (f.codfilial = t.codfilial)
            left join tblpessoa pe on (pe.codpessoa = t.codpessoa)
            left join tblcontacontabil cc on (cc.codcontacontabil = t.codcontacontabil)
            left join tblportador po on (po.codportador = coalesce(p.codportadordestino, p.codportadororigem))
            left join tblpessoa pp on (pp.codpessoa = p.codpessoa)
            where p.estado = 'E'
            and p.codnegocio is null
            and p.transacao between :de and :ate
            and {$ativa('mt')}
            {$where}
            and (
                -- saiu dinheiro
                (p.codportadororigem is not null and p.codportadordestino is null)
                -- encontro de contas e acerto do RH
                or p.meio in ({$compensacao}, {$folha})
                -- mexeu em titulo a pagar (baixa contra adiantamento com sobra, devolucao do adiantamento)
                or exists (
                    select 1 from tblmovimentotitulo x
                    inner join tbltitulo xt on (xt.codtitulo = x.codtitulo)
                    inner join tbltipotitulo xtt on (xtt.codtipotitulo = xt.codtipotitulo)
                    where x.codpagamento = p.codpagamento
                    and xtt.pagar
                    and {$ativa('x')}
                )
            )
            order by t.gerencial, f.filial, po.portador nulls last, p.transacao, p.codpagamento,
                t.vencimento, t.numero, mt.codmovimentotitulo
            limit {$limite}
        ", $params);
    }

    // fiscal/gerencial > filial > portador > pagamento > linhas, com os totais
    // de cada nivel
    public static function agrupar(array $linhas): array
    {
        $grupos = [];
        foreach ($linhas as $l) {
            $g = $l->gerencial ? 'G' : 'F';
            $f = $l->codfilial;
            $p = $l->portador ?? PagamentoService::MEIOS[$l->meio] ?? 'Sem portador';
            $grupos[$g] ??= ['rotulo' => $l->gerencial ? 'Gerencial' : 'Fiscal', 'filiais' => []];
            $filiais = &$grupos[$g]['filiais'];
            $filiais[$f] ??= ['rotulo' => $l->filial, 'portadores' => []];
            $portadores = &$filiais[$f]['portadores'];
            $portadores[$p] ??= ['rotulo' => $p, 'pagamentos' => []];
            $pagamentos = &$portadores[$p]['pagamentos'];
            $pagamentos[$l->codpagamento] ??= [
                'codpagamento' => $l->codpagamento,
                'transacao' => $l->transacao,
                'pessoa' => $l->pagamentopessoa,
                'observacoes' => $l->observacoes,
                'valor' => (float) $l->pagamentototal,
                'operacao' => static::operacao($l),
                'linhaspagamento' => (int) $l->linhaspagamento,
                'linhas' => [],
            ];
            $pagamentos[$l->codpagamento]['linhas'][] = $l;
            unset($filiais, $portadores, $pagamentos);
        }

        $somar = function (array $itens) {
            $tot = array_fill_keys(static::VALORES, 0.0);
            foreach ($itens as $i) {
                foreach (static::VALORES as $v) {
                    $tot[$v] += is_array($i) ? $i['totais'][$v] : (float) $i->{$v};
                }
            }
            return $tot;
        };
        foreach ($grupos as &$g) {
            foreach ($g['filiais'] as &$f) {
                foreach ($f['portadores'] as &$p) {
                    foreach ($p['pagamentos'] as &$pag) {
                        $pag['totais'] = $somar($pag['linhas']);
                        $pag['parcial'] = count($pag['linhas']) < $pag['linhaspagamento'];
                    }
                    unset($pag);
                    $p['totais'] = $somar($p['pagamentos']);
                }
                unset($p);
                $f['totais'] = $somar($f['portadores']);
            }
            unset($f);
            $g['totais'] = $somar($g['filiais']);
        }
        unset($g);
        // fiscal (F) antes do gerencial (G)
        ksort($grupos);

        return [
            'grupos' => $grupos,
            'totais' => $somar($grupos),
        ];
    }

    // E encontro de contas, S saiu dinheiro, R entrou, O o resto (folha sem portador)
    public static function operacao($l): string
    {
        if ((int) $l->meio == PagamentoService::MEIO_COMPENSACAO) {
            return 'E';
        }
        if (!empty($l->codportadororigem) && empty($l->codportadordestino)) {
            return 'S';
        }
        if (!empty($l->codportadordestino) && empty($l->codportadororigem)) {
            return 'R';
        }
        return 'O';
    }
}
