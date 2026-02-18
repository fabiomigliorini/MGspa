<?php

namespace Mg\Meta;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Mg\Meta\CriarMetaRequest;
use App\Http\Requests\Mg\Meta\AtualizarMetaRequest;
use Mg\MgController;
use Mg\Meta\Services\MetaAggregateService;
use Mg\Meta\Services\MetaReconstrucaoService;
use Mg\Usuario\Autorizador;

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

        $overlap = Meta::where('periodoinicial', '<=', $data['periodofinal'])
            ->where('periodofinal', '>=', $data['periodoinicial'])
            ->exists();

        if ($overlap) {
            abort(422, 'O periodo informado sobrepoe uma meta existente.');
        }

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

        MetaReconstrucaoService::reconciliarMeta($meta);

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

        MetaReconstrucaoService::reconciliarMeta($meta);
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

    public function inativar(Request $request, $codmeta)
    {
        Autorizador::autoriza(['Meta']);

        $meta = Meta::findOrFail($codmeta);
        $meta->inativo = Carbon::now();
        $meta->update();

        return new MetaResource($meta);
    }

    public function ativar(Request $request, $codmeta)
    {
        Autorizador::autoriza(['Meta']);

        $meta = Meta::findOrFail($codmeta);
        $meta->inativo = null;
        $meta->update();

        return new MetaResource($meta);
    }

    // =====================================================
    // ENDPOINTS INDIVIDUAIS — UNIDADE
    // =====================================================

    public function storeUnidade(Request $request, $codmeta)
    {
        Autorizador::autoriza(['Meta']);
        $meta = $this->metaEditavel($codmeta);

        $data = $request->validate([
            'codunidadenegocio' => ['required', 'integer', 'exists:tblunidadenegocio,codunidadenegocio'],
            'valormeta' => ['nullable', 'numeric', 'min:0'],
            'valormetacaixa' => ['nullable', 'numeric', 'min:0'],
            'valormetavendedor' => ['nullable', 'numeric', 'min:0'],
            'valormetaxerox' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaovendedor' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaovendedormeta' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaosubgerente' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaosubgerentemeta' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaoxerox' => ['nullable', 'numeric', 'min:0'],
            'premioprimeirovendedor' => ['nullable', 'numeric', 'min:0'],
            'premiosubgerentemeta' => ['nullable', 'numeric', 'min:0'],
            'premiometaxerox' => ['nullable', 'numeric', 'min:0'],
        ]);

        $existe = MetaUnidadeNegocio::where('codmeta', $codmeta)
            ->where('codunidadenegocio', $data['codunidadenegocio'])
            ->exists();

        if ($existe) {
            abort(422, "Unidade de negocio {$data['codunidadenegocio']} ja cadastrada nesta meta.");
        }

        MetaUnidadeNegocio::create(array_merge(['codmeta' => intval($codmeta)], $data));

        return new MetaResource($meta->fresh());
    }

    public function updateUnidade(Request $request, $codmeta, $codunidadenegocio)
    {
        Autorizador::autoriza(['Meta']);
        $meta = $this->metaEditavel($codmeta);

        $data = $request->validate([
            'valormeta' => ['nullable', 'numeric', 'min:0'],
            'valormetacaixa' => ['nullable', 'numeric', 'min:0'],
            'valormetavendedor' => ['nullable', 'numeric', 'min:0'],
            'valormetaxerox' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaovendedor' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaovendedormeta' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaosubgerente' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaosubgerentemeta' => ['nullable', 'numeric', 'min:0'],
            'percentualcomissaoxerox' => ['nullable', 'numeric', 'min:0'],
            'premioprimeirovendedor' => ['nullable', 'numeric', 'min:0'],
            'premiosubgerentemeta' => ['nullable', 'numeric', 'min:0'],
            'premiometaxerox' => ['nullable', 'numeric', 'min:0'],
        ]);

        $unidade = MetaUnidadeNegocio::where('codmeta', $codmeta)
            ->where('codunidadenegocio', $codunidadenegocio)
            ->firstOrFail();

        $unidade->update($data);

        return new MetaResource($meta->fresh());
    }

    public function destroyUnidade(Request $request, $codmeta, $codunidadenegocio)
    {
        Autorizador::autoriza(['Meta']);
        $meta = $this->metaEditavel($codmeta);

        DB::beginTransaction();

        MetaUnidadeNegocioPessoaFixo::where('codmeta', $codmeta)
            ->where('codunidadenegocio', $codunidadenegocio)
            ->delete();

        MetaUnidadeNegocioPessoa::where('codmeta', $codmeta)
            ->where('codunidadenegocio', $codunidadenegocio)
            ->delete();

        MetaUnidadeNegocio::where('codmeta', $codmeta)
            ->where('codunidadenegocio', $codunidadenegocio)
            ->delete();

        DB::commit();

        return new MetaResource($meta->fresh());
    }

    // =====================================================
    // ENDPOINTS INDIVIDUAIS — PESSOA
    // =====================================================

    public function storePessoa(Request $request, $codmeta, $codunidadenegocio)
    {
        Autorizador::autoriza(['Meta']);
        $meta = $this->metaEditavel($codmeta);

        MetaUnidadeNegocio::where('codmeta', $codmeta)
            ->where('codunidadenegocio', $codunidadenegocio)
            ->firstOrFail();

        $data = $request->validate([
            'codpessoa' => ['required', 'integer', 'exists:tblpessoa,codpessoa'],
            'datainicial' => ['required', 'date'],
            'datafinal' => ['required', 'date', 'after_or_equal:datainicial'],
            'percentualvenda' => ['nullable', 'numeric', 'min:0'],
            'percentualcaixa' => ['nullable', 'numeric', 'min:0'],
            'percentualsubgerente' => ['nullable', 'numeric', 'min:0'],
            'percentualxerox' => ['nullable', 'numeric', 'min:0'],
        ]);

        MetaUnidadeNegocioPessoa::create(array_merge([
            'codmeta' => intval($codmeta),
            'codunidadenegocio' => intval($codunidadenegocio),
        ], $data));

        return new MetaResource($meta->fresh());
    }

    public function updatePessoa(Request $request, $codmeta, $id)
    {
        Autorizador::autoriza(['Meta']);
        $meta = $this->metaEditavel($codmeta);

        $data = $request->validate([
            'datainicial' => ['sometimes', 'date'],
            'datafinal' => ['sometimes', 'date'],
            'percentualvenda' => ['nullable', 'numeric', 'min:0'],
            'percentualcaixa' => ['nullable', 'numeric', 'min:0'],
            'percentualsubgerente' => ['nullable', 'numeric', 'min:0'],
            'percentualxerox' => ['nullable', 'numeric', 'min:0'],
        ]);

        $pessoa = MetaUnidadeNegocioPessoa::where('codmeta', $codmeta)
            ->where('codmetaunidadenegociopessoa', $id)
            ->firstOrFail();

        $pessoa->update($data);

        return new MetaResource($meta->fresh());
    }

    public function destroyPessoa(Request $request, $codmeta, $id)
    {
        Autorizador::autoriza(['Meta']);
        $meta = $this->metaEditavel($codmeta);

        $pessoa = MetaUnidadeNegocioPessoa::where('codmeta', $codmeta)
            ->where('codmetaunidadenegociopessoa', $id)
            ->firstOrFail();

        DB::beginTransaction();

        MetaUnidadeNegocioPessoaFixo::where('codmeta', $codmeta)
            ->where('codunidadenegocio', $pessoa->codunidadenegocio)
            ->where('codpessoa', $pessoa->codpessoa)
            ->delete();

        $pessoa->delete();

        DB::commit();

        return new MetaResource($meta->fresh());
    }

    // =====================================================
    // ENDPOINTS INDIVIDUAIS — FIXO
    // =====================================================

    public function storeFixo(Request $request, $codmeta, $idPessoa)
    {
        Autorizador::autoriza(['Meta']);
        $meta = $this->metaEditavel($codmeta);

        $pessoa = MetaUnidadeNegocioPessoa::where('codmeta', $codmeta)
            ->where('codmetaunidadenegociopessoa', $idPessoa)
            ->firstOrFail();

        $data = $request->validate([
            'tipo' => ['required', 'string'],
            'valor' => ['nullable', 'numeric'],
            'quantidade' => ['nullable', 'numeric', 'min:0'],
            'descricao' => ['nullable', 'string'],
            'datainicial' => ['nullable', 'date'],
            'datafinal' => ['nullable', 'date'],
        ]);

        MetaUnidadeNegocioPessoaFixo::create(array_merge([
            'codmeta' => intval($codmeta),
            'codunidadenegocio' => $pessoa->codunidadenegocio,
            'codpessoa' => $pessoa->codpessoa,
        ], $data));

        return new MetaResource($meta->fresh());
    }

    public function updateFixo(Request $request, $codmeta, $id)
    {
        Autorizador::autoriza(['Meta']);
        $meta = $this->metaEditavel($codmeta);

        $data = $request->validate([
            'tipo' => ['sometimes', 'string'],
            'valor' => ['nullable', 'numeric'],
            'quantidade' => ['nullable', 'numeric', 'min:0'],
            'descricao' => ['nullable', 'string'],
            'datainicial' => ['nullable', 'date'],
            'datafinal' => ['nullable', 'date'],
        ]);

        $fixo = MetaUnidadeNegocioPessoaFixo::where('codmeta', $codmeta)
            ->where('codmetaunidadenegociopessoafixo', $id)
            ->firstOrFail();

        $fixo->update($data);

        return new MetaResource($meta->fresh());
    }

    public function destroyFixo(Request $request, $codmeta, $id)
    {
        Autorizador::autoriza(['Meta']);
        $meta = $this->metaEditavel($codmeta);

        MetaUnidadeNegocioPessoaFixo::where('codmeta', $codmeta)
            ->where('codmetaunidadenegociopessoafixo', $id)
            ->firstOrFail()
            ->delete();

        return new MetaResource($meta->fresh());
    }

    // =====================================================

    private function metaEditavel($codmeta): Meta
    {
        $meta = Meta::findOrFail($codmeta);

        if ($meta->status === MetaService::META_STATUS_FECHADA) {
            abort(422, "Meta #{$codmeta} esta fechada e nao pode ser alterada.");
        }

        return $meta;
    }
}
