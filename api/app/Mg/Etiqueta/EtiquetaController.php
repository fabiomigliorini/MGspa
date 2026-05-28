<?php

namespace Mg\Etiqueta;

use Illuminate\Http\Request;
use Carbon\Carbon;

use Mg\MgController;
use Mg\Produto\ProdutoService;
use Mg\Negocio\Negocio;

class EtiquetaController extends MgController
{
    public function barras(Request $request)
    {
        $request->validate([
            'barras' => ['required', 'string'],
        ]);
        if (!$pb = ProdutoService::buscaPorBarras($request->barras)) {
            abort(404, 'Produto Não Localizado!');
        }
        return new EtiquetaResource($pb);
    }

    public function negocio(Request $request)
    {
        $request->validate([
            'codnegocio' => ['required', 'integer'],
        ]);
        $n = Negocio::findOrFail($request->codnegocio);
        $pbs = collect();
        foreach ($n->NegocioProdutoBarraS as $npb) {
            $pb = $npb->ProdutoBarra;
            $pb->quantidadeetiqueta = $npb->quantidade;
            $pbs[] = $pb;
        }
        return EtiquetaResource::collection($pbs);
    }

    public function periodo(Request $request)
    {
        $request->validate([
            'datainicial' => ['required', 'date'],
            'datafinal' => ['required', 'date'],
        ]);
        $datainicial = Carbon::parse($request->datainicial)->startOfDay();
        $datafinal = Carbon::parse($request->datafinal)->endOfDay();
        $pbs = EtiquetaService::buscarProdutoBarraComPrecoAlterado($datainicial, $datafinal);
        return EtiquetaResource::collection($pbs);
    }

    public function imprimir(Request $request)
    {
        $request->validate([
            'modelo' => ['required', 'string'],
            'impressora' => ['required', 'string'],
            'etiquetas' => 'required|array|min:1',
        ]);
        return EtiquetaService::imprimir($request->impressora, $request->modelo, $request->etiquetas);
    }

    public function arquivo(Request $request, $arquivo)
    {
	$path = sys_get_temp_dir() . '/' . $arquivo;
	$conteudo = file_get_contents($path);
	unlink($path);
        return response()->make($conteudo, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'inline; filename="'.$arquivo.'"'
        ]);
    }
}
