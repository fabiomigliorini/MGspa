<?php

namespace Mg\Pagamento;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mg\Maquineta\MaquinetaService;
use Mg\PagarMe\PagarMePedidoResource;
use Mg\Pix\PixCobResource;
use Mg\Saurus\SaurusPedidoResource;
use Mg\Usuario\Autorizador;

/**
 * Cobranca integrada feita pelo contas, sem PDV (M6.1 doc-3): PIX QR e
 * cartao integrado no recebimento de titulos. Consultar e cancelar usam as
 * rotas de sempre (v1/pix/cob, v1/pdv/pagar-me, v1/pdv/saurus).
 */
class CobrancaController extends Controller
{
    private const GRUPOS = ['Administrador', 'Financeiro', 'Cobranca', 'Gerente', 'Caixa'];

    public function pix(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);
        $request->validate(['valor' => 'required|numeric|min:0.01', 'codportador' => 'required|integer']);
        return new PixCobResource(CobrancaService::pix($request->all(), null)->fresh());
    }

    public function pagarMe(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);
        $request->validate(['valor' => 'required|numeric|min:0.01', 'codpagarmepos' => 'required|integer']);
        return new PagarMePedidoResource(CobrancaService::pagarMe($request->all(), null)->fresh());
    }

    public function saurus(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);
        $request->validate(['valor' => 'required|numeric|min:0.01', 'codsauruspos' => 'required|integer']);
        return new SaurusPedidoResource(CobrancaService::saurus($request->all())->fresh());
    }

    // maquinetas que o wizard oferece na filial (as dela + compartilhadas)
    public function maquinetas(int $codfilial)
    {
        Autorizador::autoriza(self::GRUPOS);
        return ['data' => MaquinetaService::paraPdv($codfilial)];
    }
}
