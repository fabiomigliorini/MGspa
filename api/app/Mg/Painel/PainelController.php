<?php

namespace Mg\Painel;

use Illuminate\Routing\Controller;
use Mg\Usuario\Autorizador;

class PainelController extends Controller
{
    public function show(int $codfilial)
    {
        Autorizador::autoriza(['Financeiro', 'Gerente']);
        return response()->json(PainelService::filial($codfilial));
    }
}
