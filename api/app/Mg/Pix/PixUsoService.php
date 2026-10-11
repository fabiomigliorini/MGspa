<?php

namespace Mg\Pix;

use Carbon\Carbon;
use Mg\Pagamento\Pagamento;
use Mg\Pagamento\PagamentoService;
use Mg\Portador\PortadorAutorizador;
use Mg\Portador\PortadorUsuario;
use Mg\Titulo\MovimentoTitulo;
use Mg\Titulo\MovimentoTituloService;
use Mg\Titulo\TituloBoleto;

/**
 * PIX usado ou sem uso (TASK-219). O PIX que caiu na conta nao e' pagamento:
 * usar o PIX e' gravar um pagamento com ele (baixa de titulos, adiantamento,
 * a venda do PIX QR), sempre o PIX inteiro. O PIX guarda quando foi usado,
 * por quem e o pagamento; cancelado o pagamento, o PIX volta a ficar sem
 * uso, na tela PIX. Sem pagamento ligado (baixado antes do sistema pegar o
 * PIX), marca-se e desmarca-se 'a mao.
 */
class PixUsoService
{
    // Pagamento PIX com o PIX inteiro, na data e na conta dele, e o PIX
    // marcado como usado por ele. $campos: codpessoa, observacoes, codpdv,
    // codfilial.
    public static function pagar(int $codpix, float $valor, array $campos): Pagamento
    {
        $pix = Pix::lockForUpdate()->findOrFail($codpix);
        $descricao = 'O PIX de R$ ' . number_format($pix->valor, 2, ',', '.');
        if (!empty($pix->utilizacao)) {
            abort(422, "{$descricao} já foi usado!");
        }
        if (abs(round($valor, 2) - round((float) $pix->valor, 2)) > 0.005) {
            abort(422, "{$descricao} entra inteiro: os títulos precisam somar o valor dele. Se ele for maior, lance como adiantamento.");
        }
        $pag = PagamentoService::criar([
            'meio' => PagamentoService::MEIO_PIX,
            'estado' => PagamentoService::ESTADO_EFETIVADO,
            'principal' => (float) $pix->valor,
            'juros' => 0,
            'multa' => 0,
            'desconto' => 0,
            'transacao' => Carbon::parse($pix->horario),
            'efetivacao' => Carbon::now(),
            'codusuarioefetivacao' => auth()->user()->codusuario ?? null,
            'codportadordestino' => $pix->codportador,
            'codfilial' => $campos['codfilial'] ?? $pix->Portador->codfilial ?? null,
            'codpdv' => $campos['codpdv'] ?? null,
            'codpix' => $pix->codpix,
            'autorizacao' => $pix->e2eid,
            'codpessoa' => $campos['codpessoa'] ?? PixService::pessoaDoPagador($pix->cpf, $pix->cnpj),
            'observacoes' => $campos['observacoes'] ?? null,
            'referente' => $campos['referente'] ?? null,
        ]);
        static::usar($pix, $pag);
        return $pag;
    }

    // marca o PIX como usado pelo pagamento (o que ja' esta' usado fica)
    public static function usar(Pix $pix, Pagamento $pag): void
    {
        if (!empty($pix->utilizacao) || $pag->estado == PagamentoService::ESTADO_CANCELADO) {
            return;
        }
        $pix->utilizacao = Carbon::now();
        $pix->codusuarioutilizacao = auth()->user()->codusuario ?? null;
        $pix->codpagamento = $pag->codpagamento;
        $pix->save();
    }

    // o pagamento foi cancelado (o pagamento, a venda): o PIX volta a ficar
    // sem uso
    public static function pagamentoCancelado(Pagamento $pag): void
    {
        foreach (Pix::where('codpagamento', $pag->codpagamento)->get() as $pix) {
            static::limpar($pix);
        }
    }

    // PIX que pagou um boleto nosso (o txid e' o do QR do boleto): quem baixa
    // e' o retorno do boleto. Entra usado, e ganha o pagamento da baixa
    // quando ela chega. True quando e' de boleto.
    public static function boleto(Pix $pix): bool
    {
        if (empty($pix->txid)) {
            return false;
        }
        $tb = TituloBoleto::where('qrcodetxid', $pix->txid)->orderBy('codtituloboleto')->first();
        if (!$tb) {
            return false;
        }
        if (empty($pix->utilizacao)) {
            $pix->utilizacao = Carbon::now();
            $pix->codusuarioutilizacao = auth()->user()->codusuario ?? null;
        }
        if (empty($pix->codpagamento)) {
            $pix->codpagamento = MovimentoTitulo::where('codtituloboleto', $tb->codtituloboleto)
                ->where('codtipomovimentotitulo', MovimentoTituloService::TIPO_LIQUIDACAO)
                ->whereNotNull('codpagamento')
                ->orderBy('codmovimentotitulo', 'desc')
                ->value('codpagamento');
        }
        if ($pix->isDirty()) {
            $pix->save();
        }
        return true;
    }

    // a baixa do boleto chegou: o PIX que o pagou (se ja' veio) ganha o
    // pagamento dela
    public static function boletoBaixado(TituloBoleto $tb): void
    {
        if (empty($tb->qrcodetxid)) {
            return;
        }
        foreach (Pix::where('txid', $tb->qrcodetxid)->get() as $pix) {
            static::boleto($pix);
        }
    }

    // 'A mao, no PIX sem pagamento ligado: alguem ja' baixou antes do
    // sistema pegar o PIX. Operador da conta do PIX.
    public static function marcar(int $codpix): Pix
    {
        $pix = Pix::lockForUpdate()->findOrFail($codpix);
        PortadorAutorizador::autorizar($pix->Portador, PortadorUsuario::PAPEL_OPERADOR, 'Marcar o PIX como usado');
        if (!empty($pix->utilizacao)) {
            abort(422, 'Este PIX já está marcado como usado!');
        }
        $pix->utilizacao = Carbon::now();
        $pix->codusuarioutilizacao = auth()->user()->codusuario ?? null;
        $pix->codpagamento = null;
        $pix->save();
        return $pix;
    }

    // so' o marcado 'a mao: o usado por um pagamento volta cancelando o
    // pagamento
    public static function desmarcar(int $codpix): Pix
    {
        $pix = Pix::lockForUpdate()->findOrFail($codpix);
        PortadorAutorizador::autorizar($pix->Portador, PortadorUsuario::PAPEL_OPERADOR, 'Desmarcar o PIX');
        if (empty($pix->utilizacao)) {
            abort(422, 'Este PIX não está marcado como usado!');
        }
        if (!empty($pix->codpagamento)) {
            abort(422, "Este PIX foi usado no pagamento #{$pix->codpagamento}: para liberar o PIX, cancele o pagamento.");
        }
        static::limpar($pix);
        return $pix;
    }

    private static function limpar(Pix $pix): void
    {
        $pix->utilizacao = null;
        $pix->codusuarioutilizacao = null;
        $pix->codpagamento = null;
        $pix->save();
    }
}
