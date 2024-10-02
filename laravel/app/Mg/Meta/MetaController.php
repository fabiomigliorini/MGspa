<?php

namespace Mg\Meta;

use Illuminate\Http\Request;

/*
use Exception;
use Illuminate\Support\Facades\DB;
use Mg\Negocio\NegocioResource;
use Mg\Negocio\NegocioListagemResource;
use Mg\Negocio\NegocioComandaService;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioDevolucaoService;
use Mg\NotaFiscal\NotaFiscalService;
use Mg\PagarMe\PagarMePedidoResource;
use Mg\Pix\PixService;
use Mg\PagarMe\PagarMeService;
use Mg\PagarMe\PagarMePedido;
use Mg\Titulo\Titulo;
use Mg\Titulo\TituloResource;
use Mg\Titulo\TituloService;
*/

class MetaController
{

    public function index (Request $request)
    {
        $regs = Meta::orderBy('periodofinal', 'desc')->paginate();
        return MetaListagemResource::collection($regs);
    }

    public function show (Request $request, $codmeta)
    {
        $meta = Meta::findOrFail($codmeta);
        return new MetaResource($meta);
    }
}
