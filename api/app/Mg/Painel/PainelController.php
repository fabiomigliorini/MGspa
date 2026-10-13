<?php

namespace Mg\Painel;

use Illuminate\Routing\Controller;
use Mg\Conferencia\ConferenciaAutorizador;

// Financeiro e Administrador veem todas as filiais; Gerente, as filiais
// em que e' Gerente (mesma regra da conferencia)
class PainelController extends Controller
{
    public function index()
    {
        $filiais = ConferenciaAutorizador::filiais();
        if ($filiais === []) {
            abort(403, 'Só Financeiro, Administrador ou Gerente!');
        }
        return response()->json(PainelService::filiais($filiais));
    }

    public function show(int $codfilial)
    {
        ConferenciaAutorizador::autorizar($codfilial);
        return response()->json(PainelService::filial($codfilial));
    }
}
