<?php

namespace Mg\Pagamento;

use Mg\Negocio\Negocio;
use Mg\PagarMe\PagarMePos;
use Mg\PagarMe\PagarMeService;
use Mg\Pdv\Pdv;
use Mg\Pessoa\Pessoa;
use Mg\Pix\PixService;
use Mg\Saurus\SaurusPdv;
use Mg\Saurus\SaurusPinPad;
use Mg\Saurus\SaurusService;
use Ramsey\Uuid\Uuid;

/**
 * Cobranca integrada do wizard (PIX QR, Stone/PagarMe, SafraPay/Saurus) para
 * um documento: o negocio, ou nenhum (recebimento de titulo, M6.1). Feita
 * pelo PDV ou pelo contas (sem PDV). O pagamento nasce quando o banco ou a
 * maquineta confirma.
 */
class CobrancaService
{
    public static function pix(array $dados, ?Pdv $pdv)
    {
        $negocio = !empty($dados['codnegocio']) ? Negocio::findOrFail($dados['codnegocio']) : null;
        $pessoa = !empty($dados['codpessoa']) ? Pessoa::findOrFail($dados['codpessoa']) : null;
        return PixService::criarPixCobPdv(
            (float) $dados['valor'],
            $pdv,
            $negocio,
            $dados['codportador'] ?? null,
            $pessoa
        );
    }

    public static function pagarMe(array $dados, ?Pdv $pdv)
    {
        $pos = PagarMePos::findOrFail($dados['codpagarmepos']);
        PagarMeService::consultarPedidosAbertosPos($pos->codpagarmepos);
        PagarMeService::cancelarPedidosAbertosPos($pos->codpagarmepos);
        $juros = (float) ($dados['valorjuros'] ?? 0);
        return PagarMeService::criarPedido(
            $pdv->codfilial ?? $pos->codfilial,
            $pos->codpagarmepos,
            $dados['tipo'],
            $dados['valor'],
            $juros,
            $juros + (float) ($dados['valor'] ?? 0),
            $dados['valorparcela'] ?? 0,
            $dados['parcelas'],
            $dados['jurosloja'] ?? true,
            $dados['descricao'] ?? null,
            $dados['codnegocio'] ?? null,
            $pdv->codpdv ?? null,
            $dados['codpessoa'] ?? null
        );
    }

    public static function saurus(array $dados)
    {
        $pdvSaurus = SaurusPdv::findOrFail($dados['codsauruspos']);
        $pos = SaurusPinPad::where('codsauruspdv', $pdvSaurus->codsauruspdv)->firstOrFail();
        SaurusService::cancelarPedidosAbertosPdv($pdvSaurus->codsauruspdv);

        // 4 debito, 3 credito
        $modpagamento = ($dados['tipo'] == 1) ? 4 : 3;
        $juros = (float) ($dados['valorjuros'] ?? 0);
        return SaurusService::criarPedido(
            Uuid::uuid4(),
            $pdvSaurus->codsauruspdv,
            $dados['codnegocio'] ?? null,
            $dados['valor'],
            $juros,
            $juros + (float) ($dados['valor'] ?? 0),
            $dados['valorparcela'] ?? 0,
            Uuid::uuid4(),
            $modpagamento,
            $dados['parcelas'],
            0,
            auth()->user()->codusuario,
            now(),
            $pdvSaurus,
            $pos
        );
    }
}
