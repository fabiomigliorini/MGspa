<?php

namespace Mg\Caixa;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * O que os itens do caixa movimentaram, por item, filial e periodo (M13
 * doc-3): aba Itens do contas -> Caixas, para o acerto com o parceiro.
 * Qualquer um consulta.
 */
class CaixaItemLancamentoService
{
    public static function listar(array $filtros): array
    {
        $where = '';
        $params = [];
        foreach (['codcaixaitem' => 'l.codcaixaitem', 'codfilial' => 'po.codfilial'] as $chave => $coluna) {
            if (!empty($filtros[$chave])) {
                $where .= " and {$coluna} = :{$chave}";
                $params[$chave] = (int) $filtros[$chave];
            }
        }
        if (!empty($filtros['transacao_de'])) {
            $where .= ' and pp.inicio >= :de';
            $params['de'] = Carbon::parse($filtros['transacao_de'])->startOfDay();
        }
        if (!empty($filtros['transacao_ate'])) {
            $where .= ' and pp.inicio <= :ate';
            $params['ate'] = Carbon::parse($filtros['transacao_ate'])->endOfDay();
        }
        $regs = DB::select("
            select l.codcaixaitemlancamento, l.codcaixaitem, i.item, i.modo,
                pp.codportadorperiodo, pp.codportador, pp.inicio, pp.fim, po.portador, f.filial,
                l.valorabertura, l.valorentrada, l.valorsaida, l.valorvendido, l.valorfechamento,
                case when i.modo = 'C'
                    then coalesce(l.valorabertura, 0) + l.valorentrada - l.valorsaida - coalesce(l.valorfechamento, 0)
                    else l.valorentrada - l.valorsaida
                end as liquido,
                l.codtitulo, t.numero as titulo, l.observacoes
            from tblcaixaitemlancamento l
            inner join tblcaixaitem i on (i.codcaixaitem = l.codcaixaitem)
            inner join tblportadorperiodo pp on (pp.codportadorperiodo = l.codportadorperiodo)
            inner join tblportador po on (po.codportador = pp.codportador)
            left join tblfilial f on (f.codfilial = po.codfilial)
            left join tbltitulo t on (t.codtitulo = l.codtitulo)
            where (l.valorentrada <> 0 or l.valorsaida <> 0 or coalesce(l.valorvendido, 0) <> 0
                or coalesce(l.valorabertura, 0) <> 0 or coalesce(l.valorfechamento, 0) <> 0)
            {$where}
            order by i.ordem, i.item, pp.inicio desc
            limit 1000
        ", $params);
        $linhas = [];
        $totais = [];
        foreach ($regs as $r) {
            $aberta = empty($r->fim);
            $linha = [
                'codcaixaitemlancamento' => (int) $r->codcaixaitemlancamento,
                'codcaixaitem' => (int) $r->codcaixaitem,
                'item' => $r->item,
                'modo' => $r->modo,
                'codportadorperiodo' => (int) $r->codportadorperiodo,
                'codportador' => (int) $r->codportador,
                'inicio' => $r->inicio,
                'fim' => $r->fim,
                'portador' => $r->portador,
                'filial' => $r->filial,
                'valorabertura' => $r->valorabertura === null ? null : (float) $r->valorabertura,
                'valorentrada' => (float) $r->valorentrada,
                'valorsaida' => (float) $r->valorsaida,
                'valorvendido' => $r->valorvendido === null ? null : (float) $r->valorvendido,
                'valorfechamento' => $r->valorfechamento === null ? null : (float) $r->valorfechamento,
                // na contagem, com o caixa aberto o liquido ainda nao existe
                'liquido' => ($aberta && $r->modo == 'C') ? null : round((float) $r->liquido, 2),
                'codtitulo' => $r->codtitulo ? (int) $r->codtitulo : null,
                'titulo' => $r->titulo,
                'observacoes' => $r->observacoes,
            ];
            $linhas[] = $linha;
            $t = &$totais[$r->codcaixaitem];
            $t = $t ?? ['codcaixaitem' => (int) $r->codcaixaitem, 'item' => $r->item, 'modo' => $r->modo,
                'valorentrada' => 0.0, 'valorsaida' => 0.0, 'valorvendido' => 0.0, 'liquido' => 0.0, 'sessoes' => 0];
            $t['valorentrada'] = round($t['valorentrada'] + $linha['valorentrada'], 2);
            $t['valorsaida'] = round($t['valorsaida'] + $linha['valorsaida'], 2);
            $t['valorvendido'] = round($t['valorvendido'] + ($linha['valorvendido'] ?? 0), 2);
            $t['liquido'] = round($t['liquido'] + ($linha['liquido'] ?? 0), 2);
            $t['sessoes']++;
            unset($t);
        }
        return ['linhas' => $linhas, 'totais' => array_values($totais)];
    }
}
