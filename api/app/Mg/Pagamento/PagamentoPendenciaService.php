<?php

namespace Mg\Pagamento;

use Mg\Negocio\NegocioService;
use Mg\Titulo\MovimentoTitulo;

/**
 * Pagamento = fato; amarracao = outra coisa (conceito do Fabio, 09/10/2026).
 *
 * O pagamento so' registra o dinheiro que andou. O que ele pagou fica nas
 * amarracoes: movimentos de titulo (baixa, vale, adiantamento) e a venda
 * (codnegocio, o pagamento inteiro, so' quando a venda esta' fechada). Aqui
 * ficam as contas do detalhe: saldo (pago - devolvido), amarrado e livre.
 *
 * A tela "Pagamentos nao resolvidos" saiu (TASK-220): o pagamento so' nasce
 * numa tela que sabe para que ele serve (venda aberta, baixa, adiantamento);
 * o PIX sem uso fica na tela PIX (TASK-219) e o cartao fora disso se lanca
 * 'a mao.
 */
class PagamentoPendenciaService
{
    // movimentos de titulo que amarram (baixa, vale, adiantamento) e nao
    // foram estornados
    public static function movimentosAtivos(Pagamento $pag)
    {
        // a linha de estorno tem o tipo da original: quem diz que e' estorno
        // e' o codmovimentotituloestorno
        return MovimentoTitulo::where('codpagamento', $pag->codpagamento)
            ->where('codtipomovimentotitulo', '<', 900)
            ->whereNull('codmovimentotituloestorno')
            ->whereNotExists(fn ($q) => $q->selectRaw(1)->from('tblmovimentotitulo as e')
                ->whereColumn('e.codmovimentotituloestorno', 'tblmovimentotitulo.codmovimentotitulo'))
            ->get();
    }

    // pago - devolvido (cancelamento no cartao, devolucao de PIX)
    public static function saldo(Pagamento $pag): float
    {
        $devolvido = Pagamento::where('codpagamentoorigem', $pag->codpagamento)
            ->where('estado', PagamentoService::ESTADO_EFETIVADO)
            ->sum('total');
        return round(abs((float) $pag->total) - abs((float) $devolvido), 2);
    }

    // o que esta' amarrado: titulos + a venda fechada
    public static function amarrado(Pagamento $pag): float
    {
        // a soma com sinal: receber e pagar no mesmo pagamento se compensam
        // (o pagamento e' o liquido deles)
        $titulos = abs(static::movimentosAtivos($pag)->sum(fn ($m) => (float) $m->total));
        $venda = 0;
        if (!empty($pag->codnegocio) && optional($pag->Negocio)->codnegociostatus == NegocioService::STATUS_FECHADO) {
            $venda = abs((float) $pag->total);
        }
        return round($titulos + $venda, 2);
    }

    // o que ainda da' para amarrar
    public static function livre(Pagamento $pag): float
    {
        return round(static::saldo($pag) - static::amarrado($pag), 2);
    }
}
