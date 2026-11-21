<?php

namespace Mg\NotaFiscalServico;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\NotaFiscalServico\Requests\NotaFiscalServicoRequest;
use Mg\NotaFiscalServico\Resources\NotaFiscalServicoResource;

/**
 * Nota fiscal de servico (TASK-279). CRUD da nota e as acoes no Sistema Nacional
 * da NFS-e (enviar, consultar, cancelar), DANFSe, XML e e-mail.
 */
class NotaFiscalServicoController extends Controller
{
    const ORDENACAO = ['codnotafiscalservico', 'emissao', 'numero', 'nfsenumero', 'valorservico', 'status', 'criacao'];

    public function index(Request $request)
    {
        $sort = in_array($request->get('sort'), static::ORDENACAO) ? $request->get('sort') : 'codnotafiscalservico';
        $order = $request->get('order') === 'asc' ? 'asc' : 'desc';
        $query = NotaFiscalServicoService::query($request->all())
            ->with(NotaFiscalServicoService::RELACOES)
            ->orderByRaw("{$sort} {$order} nulls last")
            ->orderByDesc('codnotafiscalservico');

        return NotaFiscalServicoResource::collection(
            $query->paginate($request->get('per_page', 20))
        );
    }

    public function filiais()
    {
        return response()->json(NotaFiscalServicoService::filiais());
    }

    public function show(int $codnotafiscalservico)
    {
        return new NotaFiscalServicoResource($this->nota($codnotafiscalservico));
    }

    public function store(NotaFiscalServicoRequest $request)
    {
        $nfs = DB::transaction(fn () => NotaFiscalServicoService::criar($request->validated()));
        return (new NotaFiscalServicoResource($this->nota($nfs->codnotafiscalservico)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(NotaFiscalServicoRequest $request, int $codnotafiscalservico)
    {
        $nfs = NotaFiscalServico::findOrFail($codnotafiscalservico);
        DB::transaction(fn () => NotaFiscalServicoService::alterar($nfs, $request->validated()));
        return new NotaFiscalServicoResource($this->nota($codnotafiscalservico));
    }

    public function destroy(int $codnotafiscalservico)
    {
        $nfs = NotaFiscalServico::findOrFail($codnotafiscalservico);
        DB::transaction(fn () => NotaFiscalServicoService::excluir($nfs));
        return response()->noContent();
    }

    public function duplicar(int $codnotafiscalservico)
    {
        $original = NotaFiscalServico::findOrFail($codnotafiscalservico);
        $nova = DB::transaction(fn () => NotaFiscalServicoService::duplicar($original));
        return (new NotaFiscalServicoResource($this->nota($nova->codnotafiscalservico)))
            ->response()
            ->setStatusCode(201);
    }

    // Cria a substituta em digitacao (TASK-279 #5). O motivo pode ser trocado no formulario.
    public function substituir(Request $request, int $codnotafiscalservico)
    {
        $request->validate([
            'motivo' => 'required|integer|in:' . implode(',', array_keys(NfseXmlService::MOTIVOS_SUBSTITUICAO)),
            'justificativa' => 'nullable|string|min:15|max:255',
        ], [
            'motivo.required' => 'Informe o motivo da substituição.',
            'justificativa.min' => 'A descrição do motivo precisa de pelo menos 15 caracteres.',
        ]);
        $nova = DB::transaction(fn () => NotaFiscalServicoService::substituir(
            $codnotafiscalservico,
            (int) $request->motivo,
            $request->justificativa
        ));
        return (new NotaFiscalServicoResource($this->nota($nova->codnotafiscalservico)))
            ->response()
            ->setStatusCode(201);
    }

    // As acoes no Sistema Nacional nao rodam em transacao: cada passo grava o que o
    // Sistema Nacional confirmou, e um rollback apagaria a autorizacao ja concedida.

    public function enviar(int $codnotafiscalservico)
    {
        $resultado = NfseService::enviar($this->nota($codnotafiscalservico));
        return $this->resultado($codnotafiscalservico, $resultado);
    }

    public function consultar(int $codnotafiscalservico)
    {
        $resultado = NfseService::consultar($this->nota($codnotafiscalservico));
        return $this->resultado($codnotafiscalservico, $resultado);
    }

    public function cancelar(Request $request, int $codnotafiscalservico)
    {
        $request->validate([
            'motivo' => 'required|integer|in:' . implode(',', array_keys(NfseXmlService::MOTIVOS_CANCELAMENTO)),
            'justificativa' => 'required|string|min:15|max:255',
        ], [
            'justificativa.min' => 'A justificativa precisa de pelo menos 15 caracteres.',
        ]);
        $resultado = NfseService::cancelar(
            $this->nota($codnotafiscalservico),
            (int) $request->motivo,
            $request->justificativa
        );
        return $this->resultado($codnotafiscalservico, $resultado);
    }

    public function mail(Request $request, int $codnotafiscalservico)
    {
        $request->validate(['destinatario' => 'nullable|string|max:500']);
        return response()->json(
            NfseService::mail($this->nota($codnotafiscalservico), $request->destinatario)
        );
    }

    public function danfse(int $codnotafiscalservico)
    {
        $path = NfseService::danfse($this->nota($codnotafiscalservico));
        return response()->file($path, ['Content-Type' => 'application/pdf']);
    }

    public function xml(int $codnotafiscalservico, string $tipo)
    {
        $xml = NfseService::xml($this->nota($codnotafiscalservico), $tipo);
        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    protected function nota(int $codnotafiscalservico): NotaFiscalServico
    {
        return NotaFiscalServico::with(NotaFiscalServicoService::RELACOES)->findOrFail($codnotafiscalservico);
    }

    protected function resultado(int $codnotafiscalservico, array $resultado)
    {
        return response()->json([
            'nota' => new NotaFiscalServicoResource($this->nota($codnotafiscalservico)),
            'resultado' => $resultado,
        ]);
    }
}
