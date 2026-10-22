<?php

namespace Mg\Requisicao;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Requisicao entre lojas (TASK-55, doc-6). Todas as rotas passam pelo middleware
// `pdv`: o dispositivo diz de que loja e' quem esta' na tela.
class RequisicaoController extends Controller
{
    public function painel(Request $request)
    {
        $request->validate([
            'codestoquelocal' => 'required|integer',
            'lado' => 'required|in:atender,requisitar',
        ]);
        return response()->json(RequisicaoService::painel(
            (int) $request->codestoquelocal,
            $request->lado,
            filter_var($request->input('finalizadas', false), FILTER_VALIDATE_BOOLEAN)
        ));
    }

    public function show($codrequisicao)
    {
        return new RequisicaoResource(Requisicao::with(RequisicaoResource::RELACOES)->findOrFail($codrequisicao));
    }

    public function store(RequisicaoRequest $request)
    {
        $pdv = $request->attributes->get('pdv');
        $req = DB::transaction(fn () => RequisicaoService::criar($request->validated(), $pdv));
        return new RequisicaoResource($req->load(RequisicaoResource::RELACOES));
    }

    public function update(RequisicaoRequest $request, $codrequisicao)
    {
        $pdv = $request->attributes->get('pdv');
        $req = DB::transaction(function () use ($request, $codrequisicao, $pdv) {
            $req = Requisicao::lockForUpdate()->findOrFail($codrequisicao);
            return RequisicaoService::atualizar($req, $request->validated(), $pdv);
        });
        return new RequisicaoResource($req->load(RequisicaoResource::RELACOES));
    }

    public function cancelar(Request $request, $codrequisicao)
    {
        $pdv = $request->attributes->get('pdv');
        $req = DB::transaction(function () use ($codrequisicao, $pdv) {
            $req = Requisicao::lockForUpdate()->findOrFail($codrequisicao);
            return RequisicaoService::cancelar($req, $pdv);
        });
        return new RequisicaoResource($req->load(RequisicaoResource::RELACOES));
    }
}
