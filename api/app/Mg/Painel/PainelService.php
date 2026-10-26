<?php

namespace Mg\Painel;

use Illuminate\Support\Facades\DB;
use Mg\Conferencia\ConferenciaService;
use Mg\Filial\Filial;
use Mg\Maquineta\MaquinetaLoteService;
use Mg\Maquineta\MaquinetaService;
use Mg\Negocio\NegocioService;
use Mg\Ocorrencia\OcorrenciaService;
use Mg\Portador\PortadorService;

/**
 * Painel da filial (TASK-203): tudo que esta' pendente na filial, de
 * qualquer usuario. So' mostra; cada item leva 'a tela que resolve.
 */
class PainelService
{
    // as filiais ativas (todas, ou so' as de $codfiliais), agrupadas por
    // empresa, com o total de pendencias de cada uma: a soma dos cards do
    // painel da filial
    public static function filiais(?array $codfiliais): array
    {
        $filiais = Filial::ativo()
            ->with('Empresa:codempresa,empresa')
            ->when($codfiliais !== null, fn ($q) => $q->whereIn('codfilial', $codfiliais))
            ->orderBy('codempresa')
            ->orderBy('codfilial')
            ->get(['codfilial', 'filial', 'codempresa']);
        return $filiais->groupBy('codempresa')->map(fn ($fs) => [
            'codempresa' => $fs->first()->codempresa,
            'empresa' => $fs->first()->Empresa?->empresa,
            'filiais' => $fs->map(fn ($f) => [
                'codfilial' => $f->codfilial,
                'filial' => $f->filial,
                'total' => static::total(static::filial($f->codfilial)),
            ])->values()->all(),
        ])->values()->all();
    }

    private static function total(array $painel): int
    {
        return count($painel['caixas'])
            + count($painel['maquinetas'])
            + $painel['ocorrencias']['total']
            + count($painel['negocios'])
            + count($painel['conferencia']);
    }

    public static function filial(int $codfilial): array
    {
        return [
            'caixas' => static::caixas($codfilial),
            'maquinetas' => static::maquinetas($codfilial),
            'ocorrencias' => static::ocorrencias($codfilial),
            'negocios' => static::negocios($codfilial),
            // cheque e vale ficam de fora ate' terem tela propria
            'conferencia' => array_values(array_filter(
                ConferenciaService::consultar([$codfilial], true),
                fn ($p) => !in_array($p['tipo'], [ConferenciaService::TIPO_CHEQUE, ConferenciaService::TIPO_VALE])
            )),
        ];
    }

    // periodo aberto com movimento, periodo com diferenca a resolver ou
    // transferencia a confirmar; o periodo aberto sem movimento nao e'
    // pendencia (como na maquineta)
    private static function caixas(int $codfilial): array
    {
        $comMovimento = array_map(fn ($r) => (int) $r->codportadorperiodo, DB::select('
            select distinct pp.codportadorperiodo
            from tblportadorperiodo pp
            inner join tblportador p on (p.codportador = pp.codportador)
            inner join tblportadormovimento m on (m.codportadorperiodo = pp.codportadorperiodo)
            where p.codfilial = :codfilial
            and pp.fim is null
            and m.inativo is null
        ', ['codfilial' => $codfilial]));
        return array_values(array_filter(
            PortadorService::painel($codfilial, false, false),
            fn ($p) => (($p['sessao']['aberta'] ?? false)
                    && in_array($p['sessao']['codportadorperiodo'], $comMovimento))
                || $p['pendentes'] > 0
                || $p['chegando']['quantidade'] > 0
                || $p['saindo']['quantidade'] > 0
        ));
    }

    // periodo aberto com cartao, periodo pendente de conciliacao ou sem a
    // foto do bordero; o periodo aberto sem cartao nao e' pendencia
    private static function maquinetas(int $codfilial): array
    {
        $maquinetas = MaquinetaService::listar(['codfilial' => $codfilial, 'inativo' => false]);
        $resumo = MaquinetaLoteService::resumo($maquinetas->pluck('codmaquineta')->all());
        $ret = [];
        foreach ($maquinetas as $m) {
            $r = $resumo[$m->codmaquineta] ?? null;
            if (!$r || (!$r['abertoComMovimento'] && $r['pendentes'] == 0 && $r['semBordero'] == 0)) {
                continue;
            }
            $ret[] = [
                'codmaquineta' => $m->codmaquineta,
                'apelido' => $m->apelido,
                'aberto' => $r['abertoComMovimento'] ? $r['aberto'] : null,
                'pendentes' => $r['pendentes'],
                'semBordero' => $r['semBordero'],
            ];
        }
        return $ret;
    }

    // ocorrencias pendentes: quantas e as que mais doem
    private static function ocorrencias(int $codfilial): array
    {
        $total = DB::selectOne('
            select count(*) as total
            from tblocorrencia o
            where o.codfilial = :codfilial
            and o.conferencia is null
        ', ['codfilial' => $codfilial])->total;
        $maiores = OcorrenciaService::listar(
            'o.codfilial = :codfilial and o.conferencia is null',
            ['codfilial' => $codfilial],
            'o.valor desc nulls last, o.criacao desc',
            5
        );
        return ['total' => (int) $total, 'maiores' => $maiores];
    }

    // negocio aberto, com item, em PDV monitorado, parado ha' mais que o
    // tempo do PDV (mesma regra de OcorrenciaService::esquecidos)
    private static function negocios(int $codfilial): array
    {
        return DB::select('
            select n.codnegocio, n.valortotal, n.alteracao, p.apelido as pdv, u.usuario
            from tblnegocio n
            inner join tblpdv p on (p.codpdv = n.codpdv)
            inner join tblestoquelocal el on (el.codestoquelocal = p.codestoquelocal)
            left join tblusuario u on (u.codusuario = n.codusuario)
            where n.codnegociostatus = :aberto
            and el.codfilial = :codfilial
            and p.monitoramento is not null
            and n.criacao >= p.monitoramento
            and n.alteracao < now() - make_interval(mins => p.minutosesquecido)
            and exists (
                select 1 from tblnegocioprodutobarra i
                where i.codnegocio = n.codnegocio and i.inativo is null
            )
            order by n.alteracao
        ', ['aberto' => NegocioService::STATUS_ABERTO, 'codfilial' => $codfilial]);
    }
}
