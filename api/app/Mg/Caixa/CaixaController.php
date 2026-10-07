<?php

namespace Mg\Caixa;

use Illuminate\Routing\Controller;
use Mg\Pdv\PdvRequest;
use Mg\Pdv\PdvService;
use Mg\Portador\PortadorPeriodo;

/**
 * Caixa do PDV: a gaveta do dispositivo e se esta' aberta (o wizard bloqueia o
 * Dinheiro) e o PDF do bordero para a impressora. A tela do caixa do PDV e' a
 * do periodo do portador (v1/portador/{cod}/periodo com o codpdv).
 */
class CaixaController extends Controller
{
    // PDV: a gaveta do dispositivo e se o caixa esta' aberto (o wizard
    // bloqueia o Dinheiro com o caixa fechado)
    public function status(PdvRequest $request)
    {
        $pdv = PdvService::autoriza($request->pdv);
        $gaveta = $pdv->Portador;
        if (!$gaveta) {
            return ['data' => ['gaveta' => null, 'sessao' => null]];
        }
        $aberta = CaixaService::sessaoAberta($gaveta->codportador);
        return ['data' => [
            'gaveta' => ['codportador' => $gaveta->codportador, 'portador' => $gaveta->portador],
            'sessao' => $aberta ? ['codportadorperiodo' => $aberta->codportadorperiodo, 'inicio' => $aberta->inicio] : null,
        ]];
    }

    // PDF do bordero pela impressora (rota assinada; a tela usa
    // v1/portador-periodo/{id}/bordero)
    public function bordero(int $id)
    {
        $sessao = PortadorPeriodo::findOrFail($id);
        return response()->make(CaixaBorderoService::pdf($sessao), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Bordero' . $sessao->codportadorperiodo . '.pdf"',
        ]);
    }
}
