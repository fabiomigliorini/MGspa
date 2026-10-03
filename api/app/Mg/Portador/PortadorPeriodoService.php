<?php

namespace Mg\Portador;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Conferencia\ConferenciaService;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;

/**
 * Periodos do portador para o razao (M10 doc-3). A sessao da gaveta e' do
 * M9 (CaixaService: abre e fecha no PDV); cofre, banco e adquirente ganham
 * o periodo corrente sozinhos no primeiro lancamento (decisao 18). M12:
 * o financeiro fecha com corte (o que ficou depois vai para o corrente
 * novo), reabre do mais novo para o mais antigo e faz lancamento avulso.
 */
class PortadorPeriodoService
{
    // corrente (fim nulo) de quem nao e' gaveta; nasce no go-live com
    // saldo 0 (implantacao = lancamento avulso, M12) ou logo depois do
    // ultimo corte, com o saldo final dele
    public static function corrente(Portador $portador): PortadorPeriodo
    {
        $periodo = PortadorPeriodo::where('codportador', $portador->codportador)->whereNull('fim')->first();
        if ($periodo) {
            return $periodo;
        }
        Portador::where('codportador', $portador->codportador)->lockForUpdate()->first();
        $periodo = PortadorPeriodo::where('codportador', $portador->codportador)->whereNull('fim')->first();
        if ($periodo) {
            return $periodo;
        }
        $inicio = ConferenciaService::inicio();
        $ultimo = PortadorPeriodo::where('codportador', $portador->codportador)
            ->whereNotNull('fim')
            ->orderBy('fim', 'desc')
            ->first();
        if ($ultimo && $ultimo->fim->gt($inicio)) {
            $inicio = $ultimo->fim->copy()->addSecond();
        }
        $saldo = round((float) ($ultimo->saldofinal ?? 0), 2);
        return PortadorPeriodo::create([
            'codportador' => $portador->codportador,
            'inicio' => $inicio,
            'saldoinicial' => $saldo,
            'saldofinal' => $saldo,
        ]);
    }

    // decisao 18: o lancamento cai no periodo cuja faixa contem a sua data
    // (fechado = 422 no razao); depois do ultimo corte, no corrente
    public static function doMomento(Portador $portador, Carbon $transacao): PortadorPeriodo
    {
        $periodo = PortadorPeriodo::where('codportador', $portador->codportador)
            ->where('inicio', '<=', $transacao)
            ->whereNotNull('fim')
            ->where('fim', '>=', $transacao)
            ->orderBy('inicio', 'desc')
            ->first();
        return $periodo ?? static::corrente($portador);
    }

    // o razao nao mexe mais no periodo: gaveta fechada (fechar grava a
    // conferencia desde o M13); os demais depois de fechados (M12)
    public static function imutavel(PortadorPeriodo $periodo): bool
    {
        if (!empty($periodo->conferencia)) {
            return true;
        }
        return !empty($periodo->fechamento) && !$periodo->Portador->ehGaveta();
    }

    public static function descricao(PortadorPeriodo $periodo): string
    {
        if ($periodo->Portador->ehGaveta()) {
            return 'sessão de ' . $periodo->inicio->format('d/m/Y H:i');
        }
        return empty($periodo->fim)
            ? 'período corrente'
            : 'período de ' . $periodo->inicio->format('d/m/Y') . ' a ' . $periodo->fim->format('d/m/Y');
    }

    // ==== M12: o financeiro fecha e reabre (nao-gaveta) ====

    private static function exigirNaoGaveta(PortadorPeriodo $periodo): void
    {
        if ($periodo->Portador->ehGaveta()) {
            abort(422, 'Sessão de caixa abre e fecha no PDV e se confere em Fechamentos.');
        }
    }

    // soma das linhas ativas do periodo (ate' uma data, se informada)
    public static function movimento(PortadorPeriodo $periodo, ?Carbon $ate = null): float
    {
        $q = DB::table('tblportadormovimento')
            ->where('codportadorperiodo', $periodo->codportadorperiodo)
            ->whereNull('inativo');
        if ($ate) {
            $q->where('transacao', '<=', $ate->format('Y-m-d H:i:s'));
        }
        return round((float) $q->sum('valor'), 2);
    }

    // R13 (doc-4): o saldofinal fica sempre gravado (inicial + linhas ativas),
    // aberto ou fechado; fechar so' congela. Recalcula do periodo em diante
    // (o seguinte comeca com o final do anterior) e grava o do ultimo em
    // tblportador.saldo. Trava o portador: o lancamento concorrente soma
    // depois deste. Devolve os periodos do portador do informado em diante.
    public static function recalcular(PortadorPeriodo $periodo): Collection
    {
        DB::table('tblportador')->where('codportador', $periodo->codportador)->lockForUpdate()->first();
        $periodos = static::desde($periodo);
        $anterior = null;
        foreach ($periodos as $p) {
            if ($anterior) {
                // os fechados sao um prefixo: depois de um aberto nao ha' fechado
                if (!empty($p->fechamento)) {
                    break;
                }
                $p->saldoinicial = $anterior->saldofinal;
            }
            if (empty($p->fechamento)) {
                $p->saldofinal = round((float) $p->saldoinicial + static::movimento($p), 2);
            }
            if ($p->isDirty(['saldoinicial', 'saldofinal'])) {
                $p->save();
            }
            $anterior = $p;
        }
        $ultimo = PortadorPeriodo::where('codportador', $periodo->codportador)
            ->orderBy('inicio', 'desc')
            ->orderBy('codportadorperiodo', 'desc')
            ->first();
        DB::table('tblportador')
            ->where('codportador', $periodo->codportador)
            ->update(['saldo' => round((float) ($ultimo->saldofinal ?? 0), 2)]);
        return $periodos;
    }

    // o periodo e os seguintes do mesmo portador (os que o saldo arrasta)
    public static function desde(PortadorPeriodo $periodo): Collection
    {
        return PortadorPeriodo::where('codportador', $periodo->codportador)
            ->where(fn ($q) => $q->where('inicio', '>', $periodo->inicio)
                ->orWhere('codportadorperiodo', $periodo->codportadorperiodo))
            ->orderBy('inicio')
            ->orderBy('codportadorperiodo')
            ->get();
    }

    // R14 (doc-4): os periodos que um pagamento mexe (o de cada linha do
    // razao, ativa ou nao) e os seguintes de cada portador, atualizados
    public static function afetados(int $codpagamento): Collection
    {
        $periodos = PortadorPeriodo::whereIn('codportadorperiodo', PortadorMovimento::where('codpagamento', $codpagamento)
            ->select('codportadorperiodo'))
            ->get();
        return static::comSeguintes($periodos);
    }

    public static function comSeguintes(Collection $periodos): Collection
    {
        return $periodos
            ->groupBy('codportador')
            ->flatMap(fn ($doPortador) => static::desde($doPortador->sortBy('inicio')->first()))
            ->values();
    }

    // fecha do mais antigo para o mais novo. O corrente fecha no fim do dia
    // do corte e o que ficou depois vai para o corrente novo; o reaberto
    // (ja' tem fim) fecha no mesmo fim. Saldo final = inicial + linhas.
    public static function fechar(PortadorPeriodo $periodo, ?Carbon $corte): PortadorPeriodo
    {
        static::exigirNaoGaveta($periodo);
        $portador = $periodo->Portador;
        Portador::where('codportador', $portador->codportador)->lockForUpdate()->first();
        $periodo->refresh();
        if (!empty($periodo->fechamento)) {
            abort(422, 'Período já fechado.');
        }
        $anterior = PortadorPeriodo::where('codportador', $portador->codportador)
            ->where('inicio', '<', $periodo->inicio)
            ->whereNull('fechamento')
            ->orderBy('inicio')
            ->first();
        if ($anterior) {
            abort(422, 'Feche antes o ' . static::descricao($anterior) . ' (fecha-se do mais antigo para o mais novo).');
        }
        if ($periodo->aberto()) {
            if (!$corte) {
                abort(422, 'Informe a data de corte.');
            }
            $fim = $corte->copy()->endOfDay();
            if ($fim->lt($periodo->inicio)) {
                abort(422, 'O corte não pode ser antes do início do período (' . $periodo->inicio->format('d/m/Y') . ').');
            }
            if ($fim->gte(Carbon::today()->endOfDay())) {
                abort(422, 'O corte precisa ser um dia que já terminou (até ontem).');
            }
            $periodo->fim = $fim;
        }
        // o que ficou depois do corte vai para o periodo seguinte (o
        // corrente; nasce se preciso)
        $depois = PortadorMovimento::where('codportadorperiodo', $periodo->codportadorperiodo)
            ->whereNull('inativo')
            ->where('transacao', '>', $periodo->fim)
            ->get();
        $periodo->saldofinal = round((float) $periodo->saldoinicial + static::movimento($periodo, $periodo->fim), 2);
        $periodo->fechamento = Carbon::now();
        $periodo->codusuariofechamento = Auth::user()->codusuario ?? null;
        $periodo->save();
        $seguinte = PortadorPeriodo::where('codportador', $portador->codportador)
            ->where('inicio', '>', $periodo->inicio)
            ->orderBy('inicio')
            ->first();
        if (!$seguinte && $depois->isNotEmpty()) {
            $seguinte = static::corrente($portador);
        }
        foreach ($depois as $mov) {
            $mov->codportadorperiodo = $seguinte->codportadorperiodo;
            $mov->save();
        }
        static::recalcular($periodo);
        return $periodo;
    }

    // reabre do mais novo para o mais antigo: so' o fechado mais novo do
    // portador (os seguintes precisam estar abertos)
    public static function reabrir(PortadorPeriodo $periodo): PortadorPeriodo
    {
        static::exigirNaoGaveta($periodo);
        Portador::where('codportador', $periodo->codportador)->lockForUpdate()->first();
        $periodo->refresh();
        if (empty($periodo->fechamento)) {
            abort(422, 'Período já está aberto.');
        }
        $posterior = PortadorPeriodo::where('codportador', $periodo->codportador)
            ->where('inicio', '>', $periodo->inicio)
            ->whereNotNull('fechamento')
            ->orderBy('inicio', 'desc')
            ->first();
        if ($posterior) {
            abort(422, 'Reabra antes o ' . static::descricao($posterior) . ' (reabre-se do mais novo para o mais antigo).');
        }
        $periodo->fechamento = null;
        $periodo->codusuariofechamento = null;
        $periodo->save();
        static::recalcular($periodo);
        return $periodo;
    }

    // lancamento avulso (sem documento) de nao-gaveta: taxa, tarifa,
    // rendimento ou ajuste (o ajuste no primeiro periodo e' o saldo de
    // implantacao). Valor com sinal: positivo entrou. Cai no periodo da data
    // (fechado = 422; sem periodo ainda, nasce o corrente).
    public static function lancar(Portador $portador, string $motivo, float $valor, ?Carbon $transacao, ?string $observacoes): Pagamento
    {
        if ($portador->ehGaveta()) {
            abort(422, 'Lançamento avulso na gaveta é pelo caixa, não pelo período.');
        }
        $valor = round($valor, 2);
        if ($valor == 0) {
            abort(422, 'Informe o valor (positivo entrou, negativo saiu).');
        }
        $transacao = $transacao ?? Carbon::now();
        if ($transacao->lt(ConferenciaService::inicio())) {
            abort(422, 'O razão começa em ' . ConferenciaService::inicio()->format('d/m/Y') . ': lance a implantação nesse dia ou depois.');
        }
        $lado = $valor > 0 ? 'codportadordestino' : 'codportadororigem';
        return PagamentoService::criar([
            $lado => $portador->codportador,
            'meio' => $portador->tipo == Portador::TIPO_ESPECIE
                ? PagamentoService::MEIO_DINHEIRO
                : PagamentoService::MEIO_TRANSFERENCIA,
            'motivo' => $motivo,
            'principal' => abs($valor),
            'estado' => PagamentoService::ESTADO_EFETIVADO,
            'efetivacao' => Carbon::now(),
            'codusuarioefetivacao' => Auth::user()->codusuario ?? null,
            'transacao' => $transacao,
            'codfilial' => $portador->codfilial,
            'observacoes' => $observacoes,
        ]);
    }

    // cancela o lancamento avulso de nao-gaveta (o da gaveta e' pelo caixa);
    // periodo fechado = 422 pelo razao
    public static function cancelarLancamento(Pagamento $pag, string $justificativa): Pagamento
    {
        $portador = $pag->PortadorDestino ?? $pag->PortadorOrigem;
        if (empty($pag->motivo)
            || !empty($pag->codnegocio)
            || !empty($pag->codcaixaitemlancamento)
            || PagamentoService::ehTransferencia($pag)
            || $pag->MovimentoTituloS()->exists()
            || !$portador
        ) {
            abort(422, 'Só lançamento avulso (taxa, tarifa, rendimento, ajuste) se cancela por aqui.');
        }
        if ($portador->ehGaveta()) {
            abort(422, 'Lançamento avulso da gaveta se cancela pelo caixa.');
        }
        if ($pag->estado == PagamentoService::ESTADO_CANCELADO) {
            abort(422, 'Lançamento já cancelado.');
        }
        return PagamentoService::cancelar($pag, $justificativa);
    }
}
