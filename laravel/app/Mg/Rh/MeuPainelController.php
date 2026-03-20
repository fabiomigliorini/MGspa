<?php

namespace Mg\Rh;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class MeuPainelController extends Controller
{
    public function periodos()
    {
        $colaborador = MeuPainelService::resolverColaborador();
        if (!$colaborador) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => MeuPainelService::periodos($colaborador->codcolaborador),
        ]);
    }

    public function index(int $codperiodo)
    {
        $colaborador = MeuPainelService::resolverColaborador();
        if (!$colaborador) {
            abort(404, 'Colaborador não encontrado.');
        }

        return response()->json(
            MeuPainelService::dashboard($colaborador->codcolaborador, $codperiodo)
        );
    }

    public function colaborador(int $codperiodo, int $codperiodocolaborador)
    {
        $colaborador = MeuPainelService::resolverColaborador();
        if (!$colaborador) {
            abort(404, 'Colaborador não encontrado.');
        }

        return response()->json(
            MeuPainelService::colaboradorEquipe($colaborador->codcolaborador, $codperiodo, $codperiodocolaborador)
        );
    }
}
