<?php

namespace Mg\Requisicao;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\Impressora\Impressora;
use Mg\Negocio\Negocio;
use Mg\Pdv\PdvService;

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

    public function rascunhoDoNegocio($codnegocio)
    {
        return response()->json(RequisicaoService::rascunhoDoNegocio(
            Negocio::with(['Pessoa.Cidade.Estado'])->findOrFail($codnegocio)
        ));
    }

    public function sugestao(Request $request)
    {
        $request->validate([
            'codestoquelocalrequisitante' => 'required|integer',
            'codestoquelocalrequisitada' => 'required|integer',
            'codmarca' => 'nullable|integer',
            'abc' => 'nullable|string|max:2',
        ]);
        return response()->json(RequisicaoService::sugestao(
            (int) $request->codestoquelocalrequisitante,
            (int) $request->codestoquelocalrequisitada,
            $request->codmarca ? (int) $request->codmarca : null,
            $request->abc
        ));
    }

    public function doNegocio($codnegocio)
    {
        return response()->json(RequisicaoService::doNegocio((int) $codnegocio));
    }

    public function show($codrequisicao)
    {
        return new RequisicaoResource(Requisicao::with(RequisicaoResource::RELACOES)->findOrFail($codrequisicao));
    }

    // nasce e ja' manda as duas vias para as impressoras padrao (depois do commit);
    // o que nao tem impressora volta em "avisos", para a tela mandar pelo botao
    public function store(RequisicaoRequest $request)
    {
        $pdv = $request->attributes->get('pdv');
        $req = DB::transaction(function () use ($request, $pdv) {
            $req = RequisicaoService::criar($request->validated(), $pdv);
            ImprimirRequisicaoJob::dispatch($req->codrequisicao)->onQueue('high')->afterCommit();
            return $req;
        });
        $req->load(RequisicaoResource::RELACOES);
        return (new RequisicaoResource($req))
            ->additional(['avisos' => RequisicaoImpressaoService::avisosImpressora($req)]);
    }

    // PDF de uma via. Pela URL assinada (o MGprint baixa) ou logado: a via da loja que
    // pede tem o codigo de retirada, so' o dispositivo dela (ou Gerente) abre
    public function pdf(Request $request, $codrequisicao)
    {
        $request->validate([
            'via' => 'required|in:' . implode(',', RequisicaoImpressaoService::VIAS),
            'formato' => 'nullable|in:' . implode(',', RequisicaoImpressaoService::FORMATOS),
        ]);
        $req = Requisicao::with(RequisicaoResource::RELACOES)->findOrFail($codrequisicao);
        if (!$request->hasValidSignature() && $request->via === RequisicaoImpressaoService::VIA_REQUISITANTE) {
            $pdv = PdvService::podeAcessar(PdvService::uuid($request)) ?: null;
            if (!RequisicaoService::ehRequisitante($req, $pdv) && !RequisicaoService::ehGerente()) {
                abort(403, 'A via do cliente (com o código de retirada) só abre no dispositivo da loja que pediu!');
            }
        }
        $pdf = RequisicaoImpressaoService::pdf($req, $request->via, $request->formato ?? RequisicaoImpressaoService::FORMATO_TERMICA);
        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Requisicao' . $codrequisicao . '.pdf"',
        ]);
    }

    // manda uma via para a impressora escolhida (ou a padrao daquela via)
    public function imprimir(Request $request, $codrequisicao)
    {
        $request->validate([
            'via' => 'required|in:' . implode(',', RequisicaoImpressaoService::VIAS),
            'impressora' => 'nullable|string',
        ]);
        $pdv = $request->attributes->get('pdv');
        $req = Requisicao::with(RequisicaoResource::RELACOES)->findOrFail($codrequisicao);
        if ($request->via === RequisicaoImpressaoService::VIA_REQUISITANTE
            && !RequisicaoService::ehRequisitante($req, $pdv) && !RequisicaoService::ehGerente()) {
            abort(403, 'A via do cliente (com o código de retirada) só imprime na loja que pediu!');
        }
        $impressora = $request->filled('impressora')
            ? Impressora::where('impressora', $request->impressora)->firstOrFail()
            : RequisicaoImpressaoService::impressoraPadrao($req, $request->via);
        if (!$impressora) {
            abort(422, 'Escolha a impressora: esta via não tem impressora padrão.');
        }
        RequisicaoImpressaoService::imprimir($req, $request->via, $impressora);
        return response()->json(['mensagem' => "Enviado para {$impressora->descricao}"]);
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
