<?php

namespace Mg\Portador;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Mg\Caixa\CaixaService;

/**
 * Ajuste e transferencia (doc-4, redefinicao do dinheiro): nao sao
 * pagamento, sao linhas do movimento do portador.
 *   Ajuste: so' a linha, com observacao; aceita uma diferenca sem explicacao.
 *   Transferencia: sai de um portador e entra no outro, duas linhas ligadas
 *   pelo par, com o mesmo estado (este service mantem as duas iguais). Nasce
 *   feita se quem registra e' gestor do destino; senao fica a confirmar ate'
 *   um gestor do destino confirmar.
 * Cancelar so' com justificativa (fica em "Mostrar cancelados"), com o
 * periodo nao fechado (nos dois lados). No contas o lancamento cai no periodo
 * da tela; o outro lado da transferencia, pela data. `$livre` = a gaveta do
 * PDV que esta' lancando: o PDV nao valida o papel nela.
 */
class PortadorLancamentoService
{
    private static function usuario(): ?int
    {
        return Auth::user()->codusuario ?? null;
    }

    private static function pode(Portador $portador, string $papel, ?int $livre): bool
    {
        return $portador->codportador == $livre || PortadorAutorizador::pode($portador->codportador, $papel);
    }

    private static function travar(array $codportadores): void
    {
        $cods = array_values(array_unique($codportadores));
        sort($cods);
        Portador::whereIn('codportador', $cods)->orderBy('codportador')->lockForUpdate()->get();
    }

    // a data dentro do periodo: do inicio ao fim (aberto, ate' agora); sem
    // data, o fim (pendente) ou agora
    private static function dataNoPeriodo(PortadorPeriodo $periodo, ?Carbon $transacao): Carbon
    {
        $agora = Carbon::now()->startOfSecond();
        $ate = $periodo->fim && $periodo->fim->lt($agora) ? $periodo->fim : $agora;
        $transacao = $transacao ?? $ate;
        if ($transacao->gt($agora)) {
            abort(422, 'A data não pode ser no futuro.');
        }
        if ($transacao->lt($periodo->inicio) || $transacao->gt($ate)) {
            abort(422, 'A data precisa estar dentro do ' . PortadorPeriodoService::descricao($periodo)
                . ' (de ' . $periodo->inicio->format('d/m/Y H:i') . ' a ' . $ate->format('d/m/Y H:i') . ').');
        }
        return $transacao;
    }

    private static function exigirNaoFechado(PortadorPeriodo $periodo): void
    {
        if ($periodo->fechado()) {
            abort(422, 'O ' . PortadorPeriodoService::descricao($periodo) . " de {$periodo->Portador->portador} já foi fechado: reabra antes.");
        }
    }

    // ==== ajuste ====

    // valor com sinal: positivo entrou
    public static function ajustar(PortadorPeriodo $periodo, float $valor, string $observacoes, ?Carbon $transacao = null, ?int $livre = null): PortadorMovimento
    {
        $portador = $periodo->Portador;
        if (!static::pode($portador, PortadorUsuario::PAPEL_OPERADOR, $livre)) {
            PortadorAutorizador::autorizar($portador, PortadorUsuario::PAPEL_OPERADOR, 'Lançar ajuste');
        }
        static::travar([$portador->codportador]);
        $periodo->refresh();
        static::exigirNaoFechado($periodo);
        $valor = round($valor, 2);
        if ($valor == 0) {
            abort(422, 'Informe o valor do ajuste.');
        }
        $observacoes = trim($observacoes);
        if (mb_strlen($observacoes) < 3) {
            abort(422, 'Diga o motivo do ajuste na observação.');
        }
        $mov = PortadorMovimento::create([
            'codportador' => $portador->codportador,
            'codportadorperiodo' => $periodo->codportadorperiodo,
            'tipo' => PortadorMovimento::TIPO_AJUSTE,
            'estado' => PortadorMovimento::ESTADO_EFETIVADO,
            'valor' => $valor,
            'transacao' => static::dataNoPeriodo($periodo, $transacao),
            'observacoes' => mb_substr($observacoes, 0, 300),
        ]);
        PortadorPeriodoService::recalcular($periodo);
        return $mov;
    }

    public static function cancelarAjuste(PortadorMovimento $mov, string $justificativa, ?int $livre = null): PortadorMovimento
    {
        if ($mov->tipo != PortadorMovimento::TIPO_AJUSTE) {
            abort(422, 'Não é um ajuste.');
        }
        $portador = $mov->Portador;
        if (!static::pode($portador, PortadorUsuario::PAPEL_OPERADOR, $livre)) {
            PortadorAutorizador::autorizar($portador, PortadorUsuario::PAPEL_OPERADOR, 'Cancelar ajuste');
        }
        static::travar([$portador->codportador]);
        $mov->refresh();
        if ($mov->estado == PortadorMovimento::ESTADO_CANCELADO) {
            abort(422, 'Ajuste já cancelado.');
        }
        static::exigirNaoFechado($mov->PortadorPeriodo);
        static::cancelarLinha($mov, $justificativa);
        PortadorPeriodoService::recalcular($mov->PortadorPeriodo);
        return $mov;
    }

    private static function cancelarLinha(PortadorMovimento $mov, string $justificativa): void
    {
        $mov->fill([
            'estado' => PortadorMovimento::ESTADO_CANCELADO,
            'cancelamento' => Carbon::now(),
            'codusuariocancelamento' => static::usuario(),
            'justificativa' => mb_substr(trim($justificativa), 0, 300),
        ]);
        $mov->save();
    }

    // ==== transferencia ====

    // o periodo de cada lado: o da tela, se for dele; senao o da data (na
    // especie, o que contem a data e nao esta' fechado)
    private static function periodoDoLado(Portador $portador, Carbon $transacao, ?PortadorPeriodo $tela): PortadorPeriodo
    {
        if ($tela && $tela->codportador == $portador->codportador) {
            return $tela;
        }
        $periodo = $portador->ehCaixa()
            ? CaixaService::sessaoDoMomento($portador, $transacao)
            : PortadorPeriodoService::doMomento($portador, $transacao);
        static::exigirNaoFechado($periodo);
        return $periodo;
    }

    // registra; devolve a linha de saida (a da origem)
    public static function transferir(Portador $origem, Portador $destino, float $valor, ?string $observacoes = null, ?Carbon $transacao = null, ?PortadorPeriodo $tela = null, ?int $livre = null): PortadorMovimento
    {
        if ($origem->codportador == $destino->codportador) {
            abort(422, 'Origem e destino são o mesmo portador.');
        }
        $valor = round($valor, 2);
        if ($valor <= 0) {
            abort(422, 'O valor da transferência precisa ser maior que zero.');
        }
        if (!static::pode($origem, PortadorUsuario::PAPEL_OPERADOR, $livre)) {
            PortadorAutorizador::autorizar($origem, PortadorUsuario::PAPEL_OPERADOR, 'Transferir saindo');
        }
        if (!static::pode($destino, PortadorUsuario::PAPEL_DEPOSITANTE, $livre)) {
            PortadorAutorizador::autorizar($destino, PortadorUsuario::PAPEL_DEPOSITANTE, 'Transferir chegando');
        }
        static::travar([$origem->codportador, $destino->codportador]);
        if ($tela) {
            $tela->refresh();
            static::exigirNaoFechado($tela);
            $transacao = static::dataNoPeriodo($tela, $transacao);
        } else {
            $transacao = $transacao ?? Carbon::now()->startOfSecond();
            if ($transacao->gt(Carbon::now())) {
                abort(422, 'A data não pode ser no futuro.');
            }
        }
        $periodoOrigem = static::periodoDoLado($origem, $transacao, $tela);
        $periodoDestino = static::periodoDoLado($destino, $transacao, $tela);
        $feita = PortadorAutorizador::pode($destino->codportador, PortadorUsuario::PAPEL_GESTOR);
        $comum = [
            'tipo' => PortadorMovimento::TIPO_TRANSFERENCIA,
            'estado' => $feita ? PortadorMovimento::ESTADO_EFETIVADO : PortadorMovimento::ESTADO_PENDENTE,
            'transacao' => $transacao,
            'observacoes' => empty(trim($observacoes ?? '')) ? null : mb_substr(trim($observacoes), 0, 300),
            'confirmacao' => $feita ? Carbon::now() : null,
            'codusuarioconfirmacao' => $feita ? static::usuario() : null,
        ];
        $saida = PortadorMovimento::create($comum + [
            'codportador' => $origem->codportador,
            'codportadorperiodo' => $periodoOrigem->codportadorperiodo,
            'valor' => -$valor,
        ]);
        $entrada = PortadorMovimento::create($comum + [
            'codportador' => $destino->codportador,
            'codportadorperiodo' => $periodoDestino->codportadorperiodo,
            'valor' => $valor,
            'codportadormovimentopar' => $saida->codportadormovimento,
        ]);
        $saida->codportadormovimentopar = $entrada->codportadormovimento;
        $saida->save();
        PortadorPeriodoService::recalcular($periodoOrigem);
        PortadorPeriodoService::recalcular($periodoDestino);
        return $saida;
    }

    // as duas linhas da transferencia: [saida, entrada]
    public static function lados(PortadorMovimento $mov): array
    {
        if ($mov->tipo != PortadorMovimento::TIPO_TRANSFERENCIA || !$mov->Par) {
            abort(422, 'Não é uma transferência.');
        }
        return $mov->valor < 0 ? [$mov, $mov->Par] : [$mov->Par, $mov];
    }

    public static function podeConfirmar(PortadorMovimento $mov): bool
    {
        if ($mov->tipo != PortadorMovimento::TIPO_TRANSFERENCIA || $mov->estado != PortadorMovimento::ESTADO_PENDENTE) {
            return false;
        }
        $codDestino = $mov->valor > 0 ? $mov->codportador : optional($mov->Par)->codportador;
        return $codDestino && PortadorAutorizador::pode($codDestino, PortadorUsuario::PAPEL_GESTOR);
    }

    // cancela: operador da origem ou gestor do destino
    public static function podeCancelar(PortadorMovimento $mov, ?int $livre = null): bool
    {
        if ($mov->tipo != PortadorMovimento::TIPO_TRANSFERENCIA || $mov->estado == PortadorMovimento::ESTADO_CANCELADO) {
            return false;
        }
        $par = $mov->Par;
        if (!$par) {
            return false;
        }
        [$saida, $entrada] = $mov->valor < 0 ? [$mov, $par] : [$par, $mov];
        return in_array($livre, [$saida->codportador, $entrada->codportador])
            || PortadorAutorizador::pode($saida->codportador, PortadorUsuario::PAPEL_OPERADOR)
            || PortadorAutorizador::pode($entrada->codportador, PortadorUsuario::PAPEL_GESTOR);
    }

    public static function confirmar(PortadorMovimento $mov): PortadorMovimento
    {
        [$saida, $entrada] = static::lados($mov);
        static::travar([$saida->codportador, $entrada->codportador]);
        $saida->refresh();
        $entrada->refresh();
        if ($entrada->estado != PortadorMovimento::ESTADO_PENDENTE) {
            abort(422, 'A transferência não está a confirmar.');
        }
        PortadorAutorizador::autorizar($entrada->Portador, PortadorUsuario::PAPEL_GESTOR, 'Confirmar transferência');
        foreach ([$saida, $entrada] as $l) {
            $l->fill([
                'estado' => PortadorMovimento::ESTADO_EFETIVADO,
                'confirmacao' => Carbon::now(),
                'codusuarioconfirmacao' => static::usuario(),
            ]);
            $l->save();
        }
        return $saida;
    }

    public static function cancelarTransferencia(PortadorMovimento $mov, string $justificativa, ?int $livre = null): PortadorMovimento
    {
        [$saida, $entrada] = static::lados($mov);
        static::travar([$saida->codportador, $entrada->codportador]);
        $saida->refresh();
        $entrada->refresh();
        if ($saida->estado == PortadorMovimento::ESTADO_CANCELADO) {
            abort(422, 'Transferência já cancelada.');
        }
        if (!static::podeCancelar($saida, $livre)) {
            abort(403, "Cancelar a transferência: só operador de {$saida->Portador->portador} ou gestor de {$entrada->Portador->portador}.");
        }
        static::exigirNaoFechado($saida->PortadorPeriodo);
        static::exigirNaoFechado($entrada->PortadorPeriodo);
        static::cancelarLinha($saida, $justificativa);
        static::cancelarLinha($entrada, $justificativa);
        PortadorPeriodoService::recalcular($saida->PortadorPeriodo);
        PortadorPeriodoService::recalcular($entrada->PortadorPeriodo);
        return $saida;
    }

    // transferencias a confirmar no periodo (fechar recusa), chegando ou saindo
    public static function pendentes(PortadorPeriodo $periodo): int
    {
        return PortadorMovimento::where('codportadorperiodo', $periodo->codportadorperiodo)
            ->where('tipo', PortadorMovimento::TIPO_TRANSFERENCIA)
            ->where('estado', PortadorMovimento::ESTADO_PENDENTE)
            ->count();
    }
}
