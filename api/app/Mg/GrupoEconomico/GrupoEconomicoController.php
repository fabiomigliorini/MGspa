<?php

namespace Mg\GrupoEconomico;

use Illuminate\Http\Request;
use Mg\MgController;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Autorizador;

class GrupoEconomicoController extends MgController
{
    public function index(Request $request)
    {
        $pesquisa = '%' . ($request->nome ?? '') . '%';
        $grupos = GrupoEconomicoService::index($pesquisa);
        return GrupoEconomicoListResource::collection($grupos);
    }

    public function create(Request $request)
    {
        Autorizador::autoriza(['Financeiro']);
        $grupo = GrupoEconomicoService::create($request->all());
        return new GrupoEconomicoResource($grupo);
    }

    public function show(Request $request, $codgrupoeconomico)
    {
        $grupo = GrupoEconomico::findOrFail($codgrupoeconomico);
        return new GrupoEconomicoResource($grupo);
    }

    public function update(Request $request, $codgrupoeconomico)
    {
        Autorizador::autoriza(['Financeiro']);
        $grupo = GrupoEconomico::findOrFail($codgrupoeconomico);
        $grupo = GrupoEconomicoService::update($grupo, $request->all());
        return new GrupoEconomicoResource($grupo);
    }

    public function delete(Request $request, $codgrupoeconomico)
    {
        Autorizador::autoriza(['Financeiro']);
        $grupo = GrupoEconomico::findOrFail($codgrupoeconomico);
        GrupoEconomicoService::delete($grupo);
        return response()->json(['result' => true], 200);
    }

    public function pesquisaGrupoEconomico(Request $request)
    {
        if ($request->grupoeconomico) {
            $search = GrupoEconomico::where('grupoeconomico', 'ilike', "%{$request->grupoeconomico}%")->get();
            return response()->json($search);
        }

        if ($request->codgrupoeconomico) {
            $search = GrupoEconomico::where('codgrupoeconomico', $request->codgrupoeconomico)->get();
            return response()->json($search);
        }

        return response()->json('Nenhum resultado encontrado');
    }

    public function deletaPessoadoGrupo(Request $request, $codpessoa, $codgrupoeconomico)
    {
        Autorizador::autoriza(['Financeiro']);
        $pessoa = Pessoa::findOrFail($codpessoa);
        GrupoEconomicoService::removerDoGrupo($pessoa);

        return ['message' => true];
    }

    public function inativar($codgrupoeconomico)
    {
        Autorizador::autoriza(['Financeiro']);
        $grupo = GrupoEconomico::findOrFail($codgrupoeconomico);
        $grupo = GrupoEconomicoService::inativar($grupo);
        return new GrupoEconomicoResource($grupo);
    }

    public function ativar($codgrupoeconomico)
    {
        Autorizador::autoriza(['Financeiro']);
        $grupo = GrupoEconomico::findOrFail($codgrupoeconomico);
        $grupo = GrupoEconomicoService::ativar($grupo);
        return new GrupoEconomicoResource($grupo);
    }

    public function totaisNegocios(Request $request, $codgrupoeconomico)
    {
        $result = GrupoEconomicoService::totaisNegocios($request->all(), $codgrupoeconomico);
        return response()->json($result, 200);
    }

    public function titulosAbertos(Request $request, $codgrupoeconomico)
    {
        $result = GrupoEconomicoService::titulosAbertos($request->all(), $codgrupoeconomico);
        return response()->json($result, 200);
    }

    public function nfeTerceiro(Request $request, $codgrupoeconomico)
    {
        $result = GrupoEconomicoService::nfeTerceiro($request->all(), $codgrupoeconomico);
        return response()->json($result, 200);
    }

    public function negocios(Request $request, $codgrupoeconomico)
    {
        $result = GrupoEconomicoService::negocios($request->codpessoa, $codgrupoeconomico, $request->desde);
        return response()->json($result, 200);
    }

    public function topProdutos(Request $request, $codgrupoeconomico)
    {
        $result = GrupoEconomicoService::topProdutos($request->codpessoa, $codgrupoeconomico, $request->desde);
        return response()->json($result, 200);
    }
}
