<?php

namespace Mg\Meta;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Mg\Meta\CriarMetaRequest;
use App\Http\Requests\Mg\Meta\AtualizarMetaRequest;
use Mg\MgController;
use Mg\Meta\Services\MetaAggregateService;
use Mg\Meta\Services\ReprocessamentoMetaService;
use Mg\Permissao\Autorizador\Autorizador;

class MetaController extends MgController
{
    public function index(Request $request)
    {
        $regs = Meta::orderBy('periodofinal', 'desc')->paginate();
        return MetaListagemResource::collection($regs);
    }

    public function show(Request $request, $codmeta)
    {
        $meta = Meta::findOrFail($codmeta);
        return new MetaResource($meta);
    }

    public function store(CriarMetaRequest $request)
    {
        Autorizador::autoriza(['Meta']);

        $data = $request->validated();

        DB::beginTransaction();
        $meta = MetaAggregateService::criar($data);
        DB::commit();

        Log::info('MetaController - Meta criada via HTTP', [
            'codmeta' => $meta->codmeta,
        ]);

        return (new MetaResource($meta))
            ->response()
            ->setStatusCode(201);
    }

    public function update(AtualizarMetaRequest $request, $codmeta)
    {
        Autorizador::autoriza(['Meta']);

        $meta = Meta::findOrFail($codmeta);
        $data = $request->validated();

        DB::beginTransaction();
        $meta = MetaAggregateService::atualizar($meta, $data);
        DB::commit();

        Log::info('MetaController - Meta atualizada via HTTP', [
            'codmeta' => $meta->codmeta,
        ]);

        return new MetaResource($meta);
    }

    public function destroy(Request $request, $codmeta)
    {
        Autorizador::autoriza(['Meta']);

        $meta = Meta::findOrFail($codmeta);

        DB::beginTransaction();
        MetaAggregateService::excluir($meta);
        DB::commit();

        Log::info('MetaController - Meta excluida via HTTP', [
            'codmeta' => $codmeta,
        ]);

        return response()->json(null, 204);
    }

    public function bloquear(Request $request, $codmeta)
    {
        Autorizador::autoriza(['Meta']);

        $meta = Meta::findOrFail($codmeta);

        if ($meta->status !== MetaService::META_STATUS_ABERTA) {
            abort(422, "Meta #{$codmeta} nao esta com status Aberta (A). Status atual: {$meta->status}");
        }

        $meta->update(['status' => MetaService::META_STATUS_BLOQUEADA]);

        Log::info('MetaController - Meta bloqueada', [
            'codmeta' => $meta->codmeta,
        ]);

        return new MetaResource($meta->fresh());
    }

    public function desbloquear(Request $request, $codmeta)
    {
        Autorizador::autoriza(['Meta']);

        $meta = Meta::findOrFail($codmeta);

        if ($meta->status !== MetaService::META_STATUS_BLOQUEADA) {
            abort(422, "Meta #{$codmeta} nao esta com status Bloqueada (B). Status atual: {$meta->status}");
        }

        $meta->update(['status' => MetaService::META_STATUS_ABERTA]);

        Log::info('MetaController - Meta desbloqueada', [
            'codmeta' => $meta->codmeta,
        ]);

        return new MetaResource($meta->fresh());
    }

    public function reprocessar(Request $request, $codmeta)
    {
        Autorizador::autoriza(['Meta']);

        $meta = Meta::where('codmeta', $codmeta)->lockForUpdate()->firstOrFail();

        if ($meta->processando) {
            abort(422, "Meta #{$codmeta} ja esta sendo processada. Aguarde.");
        }

        DB::beginTransaction();

        $meta->update(['processando' => true]);

        ReprocessamentoMetaService::reprocessar($meta);

        $meta->update(['processando' => false]);

        DB::commit();

        Log::info('MetaController - Meta reprocessada via HTTP', [
            'codmeta' => $meta->codmeta,
        ]);

        return response()->json([
            'message' => "Meta #{$codmeta} reprocessada com sucesso.",
        ]);
    }

    public function finalizar(Request $request, $codmeta)
    {
        Autorizador::autoriza(['Meta']);

        $meta = Meta::where('codmeta', $codmeta)->lockForUpdate()->firstOrFail();

        if ($meta->status !== MetaService::META_STATUS_BLOQUEADA) {
            abort(422, "Meta #{$codmeta} nao esta com status Bloqueada (B). Status atual: {$meta->status}");
        }

        if ($meta->processando) {
            abort(422, "Meta #{$codmeta} ja esta sendo processada. Aguarde.");
        }

        DB::beginTransaction();

        $meta->update(['processando' => true]);

        ReprocessamentoMetaService::reprocessar($meta);
        MetaService::apurarMovimentosFinais($meta);

        $meta->update([
            'status' => MetaService::META_STATUS_FECHADA,
            'processando' => false,
        ]);

        $novaMeta = MetaService::criarNovaMeta($meta);

        DB::commit();

        Log::info('MetaController - Meta finalizada via HTTP', [
            'codmetafechada' => $meta->codmeta,
            'codmetanova' => $novaMeta->codmeta,
        ]);

        return response()->json([
            'message' => "Meta #{$codmeta} finalizada. Nova meta #{$novaMeta->codmeta} criada.",
            'codmetafechada' => $meta->codmeta,
            'codmetanova' => $novaMeta->codmeta,
        ]);
    }
}
