<?php

namespace Mg\Painel;

use Illuminate\Support\Facades\DB;
use Mg\Conferencia\ConferenciaService;
use Mg\Maquineta\MaquinetaLoteService;
use Mg\Maquineta\MaquinetaService;
use Mg\Negocio\NegocioService;
use Mg\Portador\PortadorService;

/**
 * Painel da filial (TASK-203): tudo que esta' pendente na filial, de
 * qualquer usuario. So' mostra; cada item leva 'a tela que resolve.
 */
class PainelService
{
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

    // gaveta aberta, periodo com diferenca a resolver ou transferencia a confirmar
    private static function caixas(int $codfilial): array
    {
        return array_values(array_filter(
            PortadorService::painel($codfilial, false, false),
            fn ($p) => ($p['sessao']['aberta'] ?? false)
                || $p['pendentes'] > 0
                || $p['chegando']['quantidade'] > 0
                || $p['saindo']['quantidade'] > 0
        ));
    }

    // periodo pendente de conciliacao ou sem a foto do bordero
    private static function maquinetas(int $codfilial): array
    {
        $maquinetas = MaquinetaService::listar(['codfilial' => $codfilial, 'inativo' => false]);
        $resumo = MaquinetaLoteService::resumo($maquinetas->pluck('codmaquineta')->all());
        $ret = [];
        foreach ($maquinetas as $m) {
            $r = $resumo[$m->codmaquineta] ?? null;
            if (!$r || ($r['pendentes'] == 0 && $r['semBordero'] == 0)) {
                continue;
            }
            $ret[] = [
                'codmaquineta' => $m->codmaquineta,
                'apelido' => $m->apelido,
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
        $maiores = DB::select('
            select o.codocorrencia, o.descricao, o.valor, o.criacao, p.apelido as pdv, u.usuario
            from tblocorrencia o
            left join tblpdv p on (p.codpdv = o.codpdv)
            left join tblusuario u on (u.codusuario = o.codusuario)
            where o.codfilial = :codfilial
            and o.conferencia is null
            order by o.valor desc nulls last, o.criacao desc
            limit 5
        ', ['codfilial' => $codfilial]);
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
            left join tblusuario u on (u.codusuario = n.codusuario)
            where n.codnegociostatus = :aberto
            and p.codfilial = :codfilial
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
