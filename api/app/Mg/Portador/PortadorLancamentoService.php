<?php

namespace Mg\Portador;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Mg\Anexo\FotoService;
use Mg\Caixa\CaixaItem;
use Mg\Caixa\CaixaItemService;
use Mg\Caixa\CaixaService;
use Mg\Usuario\Autorizador;

/**
 * Ajuste, transferencia e item (doc-4, redefinicao do dinheiro e "Itens do
 * caixa"): nao sao pagamento, sao linhas do movimento do portador.
 *   Ajuste: so' a linha, com observacao; aceita uma diferenca sem explicacao.
 *   Item: a entrada (+) ou saida (-) do item do caixa no portador em especie,
 *   preco x quantidade; o item conta como cedula, vender nao lanca nada.
 *   Maquineta: o total em dinheiro do bordero da maquineta de parceiro no
 *   portador em especie (negativo = devolveu dinheiro), com a foto do bordero
 *   (opcional, pode vir depois); credito na conta corrente da maquineta.
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

    // ==== item do caixa ====

    // a entrada (sinal 1) ou saida (-1) do item no portador em especie: linhas
    // preco x quantidade, valor com o sinal
    public static function lancarItem(PortadorPeriodo $periodo, CaixaItem $item, int $sinal, array $linhas, ?string $observacoes = null, ?Carbon $transacao = null, ?int $livre = null): PortadorMovimento
    {
        $portador = $periodo->Portador;
        if (!$portador->ehCaixa()) {
            abort(422, "{$portador->portador} não é portador em espécie: {$item->item} só se lança em dinheiro vivo.");
        }
        if ($item->ehMaquineta()) {
            abort(422, "{$item->item} é maquineta de parceiro: lance pelo borderô da maquineta.");
        }
        if (!static::pode($portador, PortadorUsuario::PAPEL_OPERADOR, $livre)) {
            PortadorAutorizador::autorizar($portador, PortadorUsuario::PAPEL_OPERADOR, 'Lançar item');
        }
        static::travar([$portador->codportador]);
        $periodo->refresh();
        static::exigirNaoFechado($periodo);
        $linhas = CaixaItemService::validarEntrada($linhas);
        if ($sinal < 0) {
            static::exigirDisponivel($periodo, $item, $linhas);
        }
        $total = CaixaItemService::totalLinhas($linhas);
        $observacoes = trim($observacoes ?? '');
        $mov = PortadorMovimento::create([
            'codportador' => $portador->codportador,
            'codportadorperiodo' => $periodo->codportadorperiodo,
            'tipo' => PortadorMovimento::TIPO_ITEM,
            'estado' => PortadorMovimento::ESTADO_EFETIVADO,
            'codcaixaitem' => $item->codcaixaitem,
            'itens' => $linhas,
            'valor' => $sinal < 0 ? -$total : $total,
            'transacao' => static::dataNoPeriodo($periodo, $transacao),
            'observacoes' => $observacoes === '' ? null : mb_substr($observacoes, 0, 300),
        ]);
        PortadorPeriodoService::recalcular($periodo);
        CaixaItemService::recalcularSaldo($item);
        return $mov;
    }

    // so' sai o que esta' no caixa: o saldo inicial e as entradas do periodo,
    // menos as saidas ja' lancadas, por preco e descricao (tambem ao levar a
    // saida para outro periodo, LancamentoDataService)
    public static function exigirDisponivel(PortadorPeriodo $periodo, CaixaItem $item, array $linhas): void
    {
        $disponivel = CaixaItemService::disponivel($periodo, PortadorPeriodoService::anterior($periodo))[$item->codcaixaitem] ?? [];
        $chave = fn ($l) => number_format((float) $l['preco'], 2, '.', '') . '|' . mb_strtolower($l['descricao'] ?? '');
        $tem = [];
        foreach ($disponivel as $l) {
            $tem[$chave($l)] = $l['quantidade'];
        }
        foreach ($linhas as $l) {
            $nome = trim(($l['descricao'] ?? '') . ' ' . number_format((float) $l['preco'], 2, ',', '.'));
            $q = $tem[$chave($l)] ?? 0;
            if ($q <= 0) {
                abort(422, "{$item->item} {$nome} não está no caixa (nem no saldo inicial nem em entrada do período).");
            }
            if ($l['quantidade'] > $q) {
                abort(422, "Saída de {$l['quantidade']} × {$item->item} {$nome}: só tem {$q} no caixa.");
            }
        }
    }

    // entrada ou saida do item, ou bordero da maquineta
    public static function cancelarItem(PortadorMovimento $mov, string $justificativa, ?int $livre = null): PortadorMovimento
    {
        if (!in_array($mov->tipo, [PortadorMovimento::TIPO_ITEM, PortadorMovimento::TIPO_MAQUINETA])) {
            abort(422, 'Não é lançamento de item do caixa.');
        }
        $portador = $mov->Portador;
        if (!static::pode($portador, PortadorUsuario::PAPEL_OPERADOR, $livre)) {
            PortadorAutorizador::autorizar($portador, PortadorUsuario::PAPEL_OPERADOR, 'Cancelar item');
        }
        static::travar([$portador->codportador]);
        $mov->refresh();
        if ($mov->estado == PortadorMovimento::ESTADO_CANCELADO) {
            abort(422, 'Lançamento já cancelado.');
        }
        static::exigirNaoFechado($mov->PortadorPeriodo);
        static::cancelarLinha($mov, $justificativa);
        PortadorPeriodoService::recalcular($mov->PortadorPeriodo);
        CaixaItemService::recalcularSaldo($mov->CaixaItem);
        return $mov;
    }

    // ==== bordero da maquineta de parceiro ====

    // o total em dinheiro do bordero (com sinal: negativo devolveu dinheiro)
    // no portador em especie; a foto e' opcional (a tela avisa sem bordero)
    public static function lancarMaquineta(PortadorPeriodo $periodo, CaixaItem $item, float $valor, ?string $observacoes = null, ?Carbon $transacao = null, ?string $anexoBase64 = null, ?int $livre = null): PortadorMovimento
    {
        $portador = $periodo->Portador;
        if (!$item->ehMaquineta()) {
            abort(422, "{$item->item} não é maquineta de parceiro.");
        }
        if ($item->inativo) {
            abort(422, "{$item->item} está inativa.");
        }
        if (!$portador->ehCaixa()) {
            abort(422, "{$portador->portador} não é portador em espécie: o borderô da maquineta é o dinheiro que ficou na gaveta.");
        }
        if ($item->codfilial != $portador->codfilial) {
            abort(422, "{$item->item} é de outra filial: o borderô entra no caixa da filial da maquineta.");
        }
        if (!static::pode($portador, PortadorUsuario::PAPEL_OPERADOR, $livre)) {
            PortadorAutorizador::autorizar($portador, PortadorUsuario::PAPEL_OPERADOR, 'Lançar borderô da maquineta');
        }
        static::travar([$portador->codportador]);
        $periodo->refresh();
        static::exigirNaoFechado($periodo);
        $valor = round($valor, 2);
        if ($valor == 0) {
            abort(422, 'Informe o valor em dinheiro do borderô.');
        }
        $observacoes = trim($observacoes ?? '');
        $mov = PortadorMovimento::create([
            'codportador' => $portador->codportador,
            'codportadorperiodo' => $periodo->codportadorperiodo,
            'tipo' => PortadorMovimento::TIPO_MAQUINETA,
            'estado' => PortadorMovimento::ESTADO_EFETIVADO,
            'codcaixaitem' => $item->codcaixaitem,
            'valor' => $valor,
            'transacao' => static::dataNoPeriodo($periodo, $transacao),
            'observacoes' => $observacoes === '' ? null : mb_substr($observacoes, 0, 300),
        ]);
        PortadorPeriodoService::recalcular($periodo);
        CaixaItemService::recalcularSaldo($item);
        if (!empty($anexoBase64)) {
            FotoService::gravar(static::DISCO_FOTO, static::pastaFoto($mov->codportadormovimento), $anexoBase64);
        }
        return $mov;
    }

    // a foto do bordero depois do lancamento (nao muda valor: vale com o
    // periodo fechado), por quem opera o portador
    public static function anexarFoto(PortadorMovimento $mov, string $anexoBase64, ?int $livre = null): void
    {
        if ($mov->tipo != PortadorMovimento::TIPO_MAQUINETA || !$mov->valendo()) {
            abort(422, 'Foto só no borderô de maquineta que vale.');
        }
        if (!static::pode($mov->Portador, PortadorUsuario::PAPEL_OPERADOR, $livre)) {
            PortadorAutorizador::autorizar($mov->Portador, PortadorUsuario::PAPEL_OPERADOR, 'Anexar foto do borderô');
        }
        FotoService::gravar(static::DISCO_FOTO, static::pastaFoto($mov->codportadormovimento), $anexoBase64);
    }

    // a foto errada: exclui quem pode anexar; sem nenhuma, a linha volta a
    // "sem bordero"
    public static function excluirFoto(PortadorMovimento $mov, string $arquivo, ?int $livre = null): void
    {
        if ($mov->tipo != PortadorMovimento::TIPO_MAQUINETA || !$mov->valendo()) {
            abort(422, 'Foto só no borderô de maquineta que vale.');
        }
        if (!static::pode($mov->Portador, PortadorUsuario::PAPEL_OPERADOR, $livre)) {
            PortadorAutorizador::autorizar($mov->Portador, PortadorUsuario::PAPEL_OPERADOR, 'Excluir foto do borderô');
        }
        FotoService::excluir(static::DISCO_FOTO, static::pastaFoto($mov->codportadormovimento), $arquivo);
    }

    // ve a foto quem opera o portador (e o caixa do PDV da gaveta) e o
    // financeiro (conta corrente da maquineta)
    public static function mostrarFoto(PortadorMovimento $mov, string $arquivo, ?int $livre = null)
    {
        if (!static::pode($mov->Portador, PortadorUsuario::PAPEL_OPERADOR, $livre)) {
            Autorizador::autoriza(['Administrador', 'Financeiro']);
        }
        return FotoService::mostrar(static::DISCO_FOTO, static::pastaFoto($mov->codportadormovimento), $arquivo);
    }

    public static function fotos(int $codportadormovimento): array
    {
        return FotoService::fotos(static::DISCO_FOTO, static::pastaFoto($codportadormovimento));
    }

    // foto do bordero no disco portador-anexo, pasta = codportadormovimento
    const DISCO_FOTO = 'portador-anexo';

    private static function pastaFoto(int $codportadormovimento): string
    {
        return (string) $codportadormovimento;
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

    // o que o caixa do PDV cancela: a transferencia ainda a confirmar (sangria,
    // reforco) e o bordero da maquineta; o resto, so' no contas
    public static function cancelaNoPdv(PortadorMovimento $mov): bool
    {
        return $mov->tipo == PortadorMovimento::TIPO_MAQUINETA
            || ($mov->tipo == PortadorMovimento::TIPO_TRANSFERENCIA && $mov->estado == PortadorMovimento::ESTADO_PENDENTE);
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
