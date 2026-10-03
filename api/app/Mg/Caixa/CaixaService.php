<?php

namespace Mg\Caixa;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;
use Mg\Pdv\Pdv;
use Mg\Portador\Portador;
use Mg\Portador\PortadorPeriodo;

/**
 * Sessao da gaveta (M9 doc-3): o caixa abre e fecha o dinheiro no PDV com
 * contagem; todo dinheiro que entra ou sai da gaveta fica na sessao aberta
 * (tblpagamento.codportadorperiodo); o gerente confere no contas.
 */
class CaixaService
{
    public static function gaveta(Pdv $pdv): Portador
    {
        $portador = $pdv->Portador;
        if (!$portador || $portador->tipo !== Portador::TIPO_ESPECIE) {
            abort(422, 'Este PDV não tem gaveta (portador em espécie): peça ao administrador para vincular em Configurações → PDV.');
        }
        return $portador;
    }

    public static function sessaoAberta(int $codportador): ?PortadorPeriodo
    {
        return PortadorPeriodo::where('codportador', $codportador)->whereNull('fim')->first();
    }

    public static function ultimaSessao(int $codportador): ?PortadorPeriodo
    {
        return PortadorPeriodo::where('codportador', $codportador)
            ->orderBy('inicio', 'desc')
            ->orderBy('codportadorperiodo', 'desc')
            ->first();
    }

    // a gaveta do pagamento: o destino se for gaveta, senao a origem. Conta
    // o dinheiro e, desde o M11, a transferencia (os dois lados preenchidos,
    // inclusive deposito da gaveta no banco)
    public static function gavetaDoPagamento(Pagamento $pag): ?Portador
    {
        $transferencia = !empty($pag->codportadororigem) && !empty($pag->codportadordestino);
        if ($pag->meio != PagamentoService::MEIO_DINHEIRO && !$transferencia) {
            return null;
        }
        foreach ([$pag->codportadordestino, $pag->codportadororigem] as $codportador) {
            if (empty($codportador)) {
                continue;
            }
            $portador = Portador::find($codportador);
            if ($portador && $portador->ehGaveta()) {
                return $portador;
            }
        }
        return null;
    }

    // chamado no saving do Pagamento (via ConferenciaService::vincular):
    // dinheiro de gaveta cai na sessao aberta; sem sessao, 422. Na
    // transferencia gaveta -> gaveta fica a sessao do destino; a da origem o
    // razao acha pela data (sessaoDe)
    public static function vincular(Pagamento $pag): void
    {
        $portador = static::gavetaDoPagamento($pag);
        if (!$portador) {
            $pag->codportadorperiodo = null;
            return;
        }
        // a correcao da conferencia escolhe a sessao (a do momento do
        // pagamento, ainda nao conferida)
        if ($pag->isDirty('codportadorperiodo') && !empty($pag->codportadorperiodo)) {
            return;
        }
        if ($pag->estado == PagamentoService::ESTADO_CANCELADO) {
            // cancelar dinheiro de sessao que o caixa ja fechou: so' reabrindo
            // (o registro indevido da conferencia passa: a sessao nao foi
            // conferida)
            if ($pag->isDirty('estado') && !$pag->indevido && !empty($pag->codportadorperiodo)) {
                $sessao = PortadorPeriodo::find($pag->codportadorperiodo);
                if ($sessao && !$sessao->aberto()) {
                    abort(422, "O caixa {$portador->portador} daquele dinheiro já foi fechado ({$sessao->fim->format('d/m/Y H:i')}): o gerente precisa reabrir a sessão antes.");
                }
            }
            return;
        }
        if (!empty($pag->codportadorperiodo) && !$pag->isDirty(['codportadordestino', 'codportadororigem', 'meio'])) {
            return;
        }
        $sessao = static::sessaoAberta($portador->codportador);
        if (!$sessao) {
            abort(422, "Caixa {$portador->portador} fechado: abra o caixa no PDV antes de movimentar dinheiro.");
        }
        $pag->codportadorperiodo = $sessao->codportadorperiodo;
    }

    // gaveta que recebe o dinheiro do PDV agora: sem gaveta ou com o caixa
    // fechado o Dinheiro fica bloqueado (TASK-188 AC #34)
    public static function gavetaAberta(Pdv $pdv): Portador
    {
        $gaveta = static::gaveta($pdv);
        if (!static::sessaoAberta($gaveta->codportador)) {
            abort(422, "Caixa {$gaveta->portador} fechado: abra o caixa no PDV antes de receber em dinheiro.");
        }
        return $gaveta;
    }

    // sessao da gaveta em que estava o momento (a do pagamento corrigido)
    public static function sessaoDe(int $codportador, $momento): ?PortadorPeriodo
    {
        return PortadorPeriodo::where('codportador', $codportador)
            ->where('inicio', '<=', $momento)
            ->where(function ($q) use ($momento) {
                $q->whereNull('fim')->orWhere('fim', '>=', $momento);
            })
            ->orderBy('inicio', 'desc')
            ->first();
    }

    public static function abrir(Pdv $pdv, float $moedas, float $cedulas, ?string $observacoes): PortadorPeriodo
    {
        $gaveta = static::gaveta($pdv);
        Portador::where('codportador', $gaveta->codportador)->lockForUpdate()->first();
        if (static::sessaoAberta($gaveta->codportador)) {
            abort(422, "O caixa {$gaveta->portador} já está aberto.");
        }
        return PortadorPeriodo::create([
            'codportador' => $gaveta->codportador,
            'inicio' => Carbon::now(),
            'codusuarioabertura' => Auth::user()->codusuario,
            'moedasabertura' => round($moedas, 2),
            'cedulasabertura' => round($cedulas, 2),
            'saldoinicial' => round($moedas + $cedulas, 2),
            'observacoes' => $observacoes,
        ]);
    }

    public static function fechar(Pdv $pdv, float $moedas, float $cedulas, ?string $observacoes): PortadorPeriodo
    {
        $gaveta = static::gaveta($pdv);
        Portador::where('codportador', $gaveta->codportador)->lockForUpdate()->first();
        $sessao = static::sessaoAberta($gaveta->codportador);
        if (!$sessao) {
            abort(422, "O caixa {$gaveta->portador} não está aberto.");
        }
        // decisao 22: transferencia chegando a confirmar trava; saindo, nao
        $chegando = PagamentoService::pendentes($gaveta)
            ->where('codportadordestino', $gaveta->codportador);
        if ($chegando->isNotEmpty()) {
            abort(422, "Há {$chegando->count()} transferência(s) chegando ao caixa {$gaveta->portador} a confirmar: confirme ou cancele antes de fechar.");
        }
        $agora = Carbon::now();
        $sessao->fill([
            'fim' => $agora,
            'fechamento' => $agora,
            'codusuariofechamento' => Auth::user()->codusuario,
            'moedasfechamento' => round($moedas, 2),
            'cedulasfechamento' => round($cedulas, 2),
            'saldofinal' => round($moedas + $cedulas, 2),
            'observacoes' => trim(($sessao->observacoes ? $sessao->observacoes . "\n" : '') . ($observacoes ?? '')) ?: null,
        ]);
        $sessao->save();
        return $sessao;
    }

    // o gerente reabre a sessao (so' a ultima da gaveta, e so' sem outra
    // aberta): volta a aceitar dinheiro e desfaz a conferencia
    public static function reabrir(PortadorPeriodo $sessao): PortadorPeriodo
    {
        $ultima = static::ultimaSessao($sessao->codportador);
        if ($ultima->codportadorperiodo != $sessao->codportadorperiodo) {
            abort(422, 'Só a última sessão do caixa pode ser reaberta; feche a sessão atual e confira antes.');
        }
        $sessao->fill([
            'fim' => null,
            'fechamento' => null,
            'codusuariofechamento' => null,
            'conferencia' => null,
            'codusuarioconferencia' => null,
            'valorconferido' => null,
        ]);
        $sessao->save();
        return $sessao;
    }

    // o gerente confere o dinheiro que subiu (as cegas): so' sessao que o
    // caixa ja' fechou
    public static function conferir(PortadorPeriodo $sessao, float $valor, ?string $observacoes): PortadorPeriodo
    {
        if ($sessao->aberto()) {
            abort(422, 'O caixa ainda está aberto: o caixa fecha no PDV antes da conferência.');
        }
        if (!empty($sessao->conferencia)) {
            abort(422, 'Sessão já conferida.');
        }
        $sessao->fill([
            'conferencia' => Carbon::now(),
            'codusuarioconferencia' => Auth::user()->codusuario,
            'valorconferido' => round($valor, 2),
            'observacoes' => trim(($sessao->observacoes ? $sessao->observacoes . "\n" : '') . ($observacoes ?? '')) ?: null,
        ]);
        $sessao->save();
        return $sessao;
    }

    // desfaz so' a conferencia (o caixa continua fechado)
    public static function desconferir(PortadorPeriodo $sessao): PortadorPeriodo
    {
        $sessao->fill([
            'conferencia' => null,
            'codusuarioconferencia' => null,
            'valorconferido' => null,
        ]);
        $sessao->save();
        return $sessao;
    }

    // dinheiro do sistema na sessao: saldo inicial + entradas - saidas,
    // por documento (venda, titulo, transferencia, avulso). Transferencia
    // (M11) entra pelo razao, que tem a sessao de cada lado, e conta desde
    // o registro, ainda a confirmar (decisao 13)
    public static function dinheiro(PortadorPeriodo $sessao): array
    {
        $regs = DB::select("
            select
                case
                    when p.codnegocio is not null then 'V'
                    when exists (select 1 from tblmovimentotitulo mt where mt.codpagamento = p.codpagamento) then 'T'
                    when p.codportadororigem is not null and p.codportadordestino is not null then 'X'
                    else 'A'
                end as documento,
                sum(case when p.codportadordestino = :portador1 then p.total else 0 end) as entrada,
                sum(case when p.codportadororigem = :portador2 then p.total else 0 end) as saida,
                count(*) as quantidade
            from tblpagamento p
            where (
                (p.codportadorperiodo = :sessao1 and p.estado = 'E')
                or exists (
                    select 1 from tblportadormovimento pm
                    where pm.codpagamento = p.codpagamento
                    and pm.codportadorperiodo = :sessao2
                    and pm.inativo is null
                )
            )
            group by 1
        ", [
            'portador1' => $sessao->codportador,
            'portador2' => $sessao->codportador,
            'sessao1' => $sessao->codportadorperiodo,
            'sessao2' => $sessao->codportadorperiodo,
        ]);
        $ret = [
            'saldoinicial' => (float) $sessao->saldoinicial,
            'entrada' => 0.0,
            'saida' => 0.0,
            'documentos' => [],
        ];
        foreach ($regs as $r) {
            $ret['documentos'][] = [
                'documento' => $r->documento,
                'entrada' => (float) $r->entrada,
                'saida' => (float) $r->saida,
                'quantidade' => (int) $r->quantidade,
            ];
            $ret['entrada'] = round($ret['entrada'] + $r->entrada, 2);
            $ret['saida'] = round($ret['saida'] + $r->saida, 2);
        }
        $ret['sistema'] = round($ret['saldoinicial'] + $ret['entrada'] - $ret['saida'], 2);
        return $ret;
    }

    // o que os PDVs da gaveta movimentaram na janela da sessao, fora o
    // dinheiro: so' informacao no bordero do caixa
    public static function informativo(PortadorPeriodo $sessao): array
    {
        $fim = $sessao->fim ?? Carbon::now();
        $regs = DB::select("
            select p.meio, sum(case when p.codpagamentoorigem is null then p.total else -p.total end) as valor, count(*) as quantidade
            from tblpagamento p
            inner join tblpdv pdv on (pdv.codpdv = p.codpdv)
            where pdv.codportador = :portador
            and p.transacao between :inicio and :fim
            and p.estado = 'E'
            and p.meio <> 1
            group by p.meio
            order by p.meio
        ", [
            'portador' => $sessao->codportador,
            'inicio' => $sessao->inicio,
            'fim' => $fim,
        ]);
        $meios = array_map(fn ($r) => [
            'meio' => (int) $r->meio,
            'descricao' => PagamentoService::MEIOS[$r->meio] ?? 'Outros',
            'valor' => (float) $r->valor,
            'quantidade' => (int) $r->quantidade,
        ], $regs);
        $prazo = DB::selectOne("
            select coalesce(sum(np.valor), 0) as valor, count(*) as quantidade
            from tblnegocioparcela np
            inner join tblnegocio n on (n.codnegocio = np.codnegocio)
            inner join tblpdv pdv on (pdv.codpdv = n.codpdv)
            where pdv.codportador = :portador
            and n.codnegociostatus = 2
            and n.lancamento between :inicio and :fim
        ", [
            'portador' => $sessao->codportador,
            'inicio' => $sessao->inicio,
            'fim' => $fim,
        ]);
        $maquinetas = DB::select("
            select m.apelido as maquineta, count(*) as quantidade
            from tblpagamento p
            inner join tblpdv pdv on (pdv.codpdv = p.codpdv)
            inner join tblmaquineta m on (m.codmaquineta = p.codmaquineta)
            where pdv.codportador = :portador
            and p.transacao between :inicio and :fim
            and p.estado = 'E'
            and p.meio in (3, 4)
            group by m.apelido
            order by m.apelido
        ", [
            'portador' => $sessao->codportador,
            'inicio' => $sessao->inicio,
            'fim' => $fim,
        ]);
        return [
            'meios' => $meios,
            'prazo' => ['valor' => (float) $prazo->valor, 'quantidade' => (int) $prazo->quantidade],
            'maquinetas' => array_map(fn ($r) => ['maquineta' => $r->maquineta, 'quantidade' => (int) $r->quantidade], $maquinetas),
        ];
    }

    public static function resumo(PortadorPeriodo $sessao): array
    {
        return [
            'dinheiro' => static::dinheiro($sessao),
            'informativo' => static::informativo($sessao),
        ];
    }
}
