<?php

namespace Mg\Caixa;

use Carbon\Carbon;
use Mg\Conferencia\ConferenciaAutorizador;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoListaService;
use Mg\Pagamento\PagamentoService;
use Mg\Pagamento\TransferenciaAutorizador;
use Mg\Portador\Portador;
use Mg\Portador\PortadorPeriodoService;

/**
 * Pagina Caixas do contas e destinos da transferencia no PDV (M11 doc-3):
 * os portadores em especie com saldo, sessao e transferencias a confirmar,
 * e a listagem das transferencias.
 */
class CaixasService
{
    // saldo do portador (decisao 19) e o que ja' esta' lancado para depois
    // de hoje ("a cair")
    public static function saldo(Portador $portador): array
    {
        $hoje = PortadorPeriodoService::saldo($portador);
        $futuro = PortadorPeriodoService::saldo($portador, Carbon::create(2999, 12, 31));
        return [
            'codportador' => $portador->codportador,
            'portador' => $portador->portador,
            'saldo' => $hoje,
            'acair' => round($futuro - $hoje, 2),
        ];
    }

    // saldo de gaveta so' depois da conferencia do gerente: antes disso o
    // gerente confere as cegas (M9); os demais, quem confere a filial
    public static function saldoVisivel(Portador $portador, ?\Mg\Portador\PortadorPeriodo $ultima): bool
    {
        if (!ConferenciaAutorizador::pode($portador->codfilial)) {
            return false;
        }
        if (!$portador->ehGaveta()) {
            return true;
        }
        return $ultima && !$ultima->aberto() && !empty($ultima->conferencia);
    }

    public static function caixas(?int $codfilial): array
    {
        $portadores = Portador::where('tipo', Portador::TIPO_ESPECIE)
            ->whereNull('inativo')
            ->when($codfilial, fn ($q) => $q->where('codfilial', $codfilial))
            ->with('Filial:codfilial,filial')
            ->orderBy('codfilial')
            ->orderBy('portador')
            ->get();
        return $portadores->map(function (Portador $portador) {
            $gaveta = $portador->ehGaveta();
            $ultima = $gaveta ? CaixaService::ultimaSessao($portador->codportador) : null;
            $pendentes = PagamentoService::pendentes($portador);
            $chegando = $pendentes->where('codportadordestino', $portador->codportador);
            $saindo = $pendentes->where('codportadororigem', $portador->codportador);
            $visivel = static::saldoVisivel($portador, $ultima);
            return [
                'codportador' => $portador->codportador,
                'portador' => $portador->portador,
                'codfilial' => $portador->codfilial,
                'filial' => optional($portador->Filial)->filial,
                'ehGaveta' => $gaveta,
                'financeiro' => $portador->codportador == Portador::CAIXA_FINANCEIRO,
                'podeOperar' => TransferenciaAutorizador::podeOperar($portador),
                'saldo' => $visivel ? static::saldo($portador)['saldo'] : null,
                'sessao' => $ultima ? [
                    'codportadorperiodo' => $ultima->codportadorperiodo,
                    'inicio' => $ultima->inicio,
                    'fim' => $ultima->fim,
                    'aberta' => $ultima->aberto(),
                    'conferencia' => $ultima->conferencia,
                ] : null,
                // motivo de a gaveta nao aceitar transferencia agora
                'bloqueio' => ($gaveta && (!$ultima || !$ultima->aberto())) ? 'Caixa fechado' : null,
                'chegando' => ['quantidade' => $chegando->count(), 'valor' => round((float) $chegando->sum('total'), 2)],
                'saindo' => ['quantidade' => $saindo->count(), 'valor' => round((float) $saindo->sum('total'), 2)],
            ];
        })->values()->all();
    }

    // transferencias de/para os portadores da filial: as a confirmar
    // sempre; as demais no periodo
    public static function transferencias(array $filtros)
    {
        $q = Pagamento::query()
            ->with(array_merge(PagamentoListaService::RELACOES, [
                'PortadorOrigem.Filial:codfilial,filial',
                'PortadorDestino.Filial:codfilial,filial',
            ]))
            ->whereNull('codnegocio')
            ->whereNotNull('codportadororigem')
            ->whereNotNull('codportadordestino')
            ->whereNotExists(fn ($e) => $e->selectRaw('1')->from('tblmovimentotitulo as mt')
                ->whereColumn('mt.codpagamento', 'tblpagamento.codpagamento'));
        if (!empty($filtros['codfilial'])) {
            $q->where(fn ($w) => $w
                ->whereIn('codportadororigem', fn ($s) => $s->select('codportador')->from('tblportador')->where('codfilial', $filtros['codfilial']))
                ->orWhereIn('codportadordestino', fn ($s) => $s->select('codportador')->from('tblportador')->where('codfilial', $filtros['codfilial'])));
        }
        if (!empty($filtros['codportador'])) {
            $q->where(fn ($w) => $w->where('codportadororigem', $filtros['codportador'])
                ->orWhere('codportadordestino', $filtros['codportador']));
        }
        if (($filtros['estado'] ?? null) == PagamentoService::ESTADO_PENDENTE) {
            $q->where('estado', PagamentoService::ESTADO_PENDENTE);
        } else {
            if (!empty($filtros['estado'])) {
                $q->where('estado', $filtros['estado']);
            }
            if (!empty($filtros['transacao_de'])) {
                $q->where('transacao', '>=', Carbon::parse($filtros['transacao_de'])->startOfDay());
            }
            if (!empty($filtros['transacao_ate'])) {
                $q->where('transacao', '<=', Carbon::parse($filtros['transacao_ate'])->endOfDay());
            }
        }
        return $q->orderBy('transacao', 'desc')->orderBy('codpagamento', 'desc')->paginate(50);
    }
}
