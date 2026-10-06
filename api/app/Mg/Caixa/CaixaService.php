<?php

namespace Mg\Caixa;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;
use Mg\Pdv\Pdv;
use Mg\Portador\Portador;
use Mg\Portador\PortadorAutorizador;
use Mg\Portador\PortadorMovimento;
use Mg\Portador\PortadorPeriodo;
use Mg\Portador\PortadorPeriodoService;
use Mg\Portador\PortadorUsuario;

/**
 * O caixa do PDV (gaveta) sobre o periodo do portador em especie (doc-4,
 * redefinicao do dinheiro: abrir, contar, fechar, reabrir, ajuste e
 * transferencia sao do PortadorPeriodoService e do PortadorLancamentoService).
 * Aqui fica o que e' do PDV: a gaveta do dispositivo, o periodo em que cai o
 * dinheiro do pagamento (tblpagamento.codportadorperiodo) e o que a tela do
 * caixa e o bordero mostram.
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

    // R12 (doc-4): uma regra so' para movimentar o caixa sem sessao aberta
    public static function naoAberta(Portador $gaveta): void
    {
        abort(422, "{$gaveta->portador} não está aberto: abra o período antes de movimentar.");
    }

    public static function exigirAberta(Portador $gaveta): PortadorPeriodo
    {
        $sessao = static::sessaoAberta($gaveta->codportador);
        if (!$sessao) {
            static::naoAberta($gaveta);
        }
        return $sessao;
    }

    // a sessao do pagamento: a gravada nele ou, no lancado antes de o
    // portador ser caixa (avulso do periodo, M12), a da linha do razao
    public static function sessaoDoPagamento(Pagamento $pag): ?PortadorPeriodo
    {
        if ($pag->PortadorPeriodo) {
            return $pag->PortadorPeriodo;
        }
        $linha = PortadorMovimento::where('codpagamento', $pag->codpagamento)
            ->whereNull('inativo')
            ->with('PortadorPeriodo.Portador')
            ->get()
            ->first(fn ($l) => $l->PortadorPeriodo->Portador->ehCaixa());
        return optional($linha)->PortadorPeriodo;
    }

    public static function ultimaSessao(int $codportador): ?PortadorPeriodo
    {
        return PortadorPeriodo::where('codportador', $codportador)
            ->orderBy('inicio', 'desc')
            ->orderBy('codportadorperiodo', 'desc')
            ->first();
    }

    // o caixa do pagamento em dinheiro: o destino se for especie, senao a
    // origem (ajuste e transferencia nao sao pagamento)
    public static function gavetaDoPagamento(Pagamento $pag): ?Portador
    {
        if ($pag->meio != PagamentoService::MEIO_DINHEIRO) {
            return null;
        }
        foreach ([$pag->codportadordestino, $pag->codportadororigem] as $codportador) {
            if (empty($codportador)) {
                continue;
            }
            $portador = Portador::find($codportador);
            if ($portador && $portador->ehCaixa()) {
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
                if ($sessao && !empty($sessao->fechamento)) {
                    abort(422, "O período de {$portador->portador} daquele dinheiro já foi fechado ({$sessao->fim->format('d/m/Y H:i')}): o gestor precisa reabrir antes.");
                }
            }
            return;
        }
        if (!empty($pag->codportadorperiodo) && !$pag->isDirty(['codportadordestino', 'codportadororigem', 'meio'])) {
            return;
        }
        $momento = $pag->transacao ? Carbon::parse($pag->transacao) : Carbon::now();
        $pag->codportadorperiodo = static::sessaoDoMomento($portador, $momento)->codportadorperiodo;
    }

    // a sessao do caixa que contem o momento, nao fechada: e' nela que o
    // dinheiro daquela data cai (agora = a aberta)
    public static function sessaoDoMomento(Portador $caixa, Carbon $momento): PortadorPeriodo
    {
        $sessao = static::sessaoDe($caixa->codportador, $momento);
        if (!$sessao) {
            if ($momento->gte(Carbon::now()->subMinute())) {
                static::naoAberta($caixa);
            }
            abort(422, "Não há período de {$caixa->portador} em {$momento->format('d/m/Y H:i')}.");
        }
        if (!empty($sessao->fechamento)) {
            abort(422, "O período de {$caixa->portador} de {$momento->format('d/m/Y H:i')} já foi fechado: reabra antes de lançar nele.");
        }
        return $sessao;
    }

    // gaveta que recebe o dinheiro do PDV agora: sem gaveta ou com o caixa
    // fechado o Dinheiro fica bloqueado (TASK-188 AC #34)
    public static function gavetaAberta(Pdv $pdv): Portador
    {
        $gaveta = static::gaveta($pdv);
        static::exigirAberta($gaveta);
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

    // cedulas e moedas da contagem (chave = valor, quantidade no jsonb)
    const CEDULAS = ['200', '100', '50', '20', '10', '5', '2'];
    const MOEDAS = ['1', '0.50', '0.25', '0.10', '0.05', '0.01'];

    // quem opera o caixa no contas: operador ou gestor do portador
    public static function podeOperar(Portador $caixa): bool
    {
        return PortadorAutorizador::pode($caixa->codportador, PortadorUsuario::PAPEL_OPERADOR);
    }

    public static function autorizarOperar(Portador $caixa): void
    {
        PortadorAutorizador::autorizar($caixa, PortadorUsuario::PAPEL_OPERADOR, 'Operar o caixa');
    }

    // o dinheiro de uma contagem {face: quantidade}
    public static function totalContagem(?array $contagem): float
    {
        $total = 0.0;
        foreach ($contagem ?? [] as $face => $qtd) {
            $total += (int) $qtd * (float) $face;
        }
        return round($total, 2);
    }

    // quantidades por cedula/moeda -> [contagem limpa, moedas, cedulas]
    public static function contagem(?array $qtds): array
    {
        $limpa = [];
        $totais = ['moedas' => 0.0, 'cedulas' => 0.0];
        foreach (['cedulas' => static::CEDULAS, 'moedas' => static::MOEDAS] as $tipo => $valores) {
            foreach ($valores as $valor) {
                $qtd = (int) ($qtds[$valor] ?? 0);
                if ($qtd < 0) {
                    abort(422, 'Quantidade de cédula ou moeda não pode ser negativa.');
                }
                if ($qtd > 0) {
                    $limpa[$valor] = $qtd;
                    $totais[$tipo] += $qtd * (float) $valor;
                }
            }
        }
        return [$limpa, round($totais['moedas'], 2), round($totais['cedulas'], 2)];
    }

    // o saldo inicial do proximo periodo: a contagem final do ultimo (0 no
    // primeiro)
    public static function envelope(int $codportador): float
    {
        $ultima = static::ultimaSessao($codportador);
        return $ultima ? PortadorPeriodoService::saldoInicialSeguinte($ultima) : 0.0;
    }

    // dinheiro do sistema no periodo: saldo inicial + entradas - saidas, por
    // documento (V venda, I item do caixa, T titulo, A taxa/tarifa, J ajuste,
    // X transferencia), das linhas que valem
    public static function dinheiro(PortadorPeriodo $sessao): array
    {
        $regs = DB::select("
            select
                case
                    when m.tipo = 'A' then 'J'
                    when m.tipo = 'T' then 'X'
                    when m.tipo = 'I' then 'I'
                    when p.codnegocio is not null then 'V'
                    when p.motivo is not null then 'A'
                    else 'T'
                end as documento,
                sum(case when m.valor > 0 then m.valor else 0 end) as entrada,
                sum(case when m.valor < 0 then -m.valor else 0 end) as saida,
                count(*) as quantidade
            from tblportadormovimento m
            left join tblpagamento p on (p.codpagamento = m.codpagamento)
            where m.codportadorperiodo = :sessao
            and m.inativo is null
            and coalesce(m.estado, 'E') <> 'C'
            group by 1
        ", ['sessao' => $sessao->codportadorperiodo]);
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

    // o que a tela do caixa do PDV mostra do periodo: dinheiro, informativo e
    // os ajustes
    public static function painel(PortadorPeriodo $sessao): array
    {
        $ajustes = PortadorMovimento::where('codportadorperiodo', $sessao->codportadorperiodo)
            ->where('tipo', PortadorMovimento::TIPO_AJUSTE)
            ->with('UsuarioCriacao:codusuario,usuario')
            ->orderBy('transacao')
            ->get()
            ->map(fn (PortadorMovimento $m) => [
                'codportadormovimento' => $m->codportadormovimento,
                'transacao' => $m->transacao,
                'motivodescricao' => 'Ajuste',
                'valor' => (float) $m->valor,
                'estado' => $m->estado,
                'observacoes' => $m->observacoes,
                'justificativa' => $m->justificativa,
                'usuariocriacao' => $m->usuariocriacao,
                'podeExcluir' => !$sessao->fechado() && $m->estado != PortadorMovimento::ESTADO_CANCELADO,
            ])->all();
        return [
            'dinheiro' => static::dinheiro($sessao),
            'informativo' => static::informativo($sessao),
            'avulsos' => $ajustes,
        ];
    }
}
