<?php

namespace Mg\Titulo;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mg\Usuario\Autorizador;

class TituloBoletoController extends Controller
{
    private const GRUPOS = ['Administrador', 'Financeiro', 'Cobranca'];

    public function abertosResumo()
    {
        Autorizador::autoriza(self::GRUPOS);
        return response()->json(TituloBoletoService::abertosResumo());
    }

    public function abertosLista(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);
        $tipo = $request->get('tipo', 'vencidos');
        return response()->json(TituloBoletoService::abertosLista($tipo));
    }

    public function liquidadosNavegacao(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);
        return response()->json(TituloBoletoService::liquidadosNavegacao(
            $request->get('ano'),
            $request->get('mes'),
            $request->get('dia'),
            $request->get('codportador') ? (int) $request->get('codportador') : null,
        ));
    }

    public function baixadosLista(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS);
        return response()->json(TituloBoletoService::baixadosLista(
            $request->only(['codportador', 'tipobaixa'])
        ));
    }
}
