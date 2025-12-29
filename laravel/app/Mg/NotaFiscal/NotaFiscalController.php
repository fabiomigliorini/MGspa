<?php

namespace Mg\NotaFiscal;

use App\Http\Controllers\Controller;
use Mg\NotaFiscal\Requests\NotaFiscalRequest;
use Mg\NotaFiscal\Resources\NotaFiscalResource;
use Mg\NotaFiscal\Resources\NotaFiscalDetailResource;
use Illuminate\Http\Request;

class NotaFiscalController extends Controller
{
    /**
     * Statuses que impedem modificações na nota
     */
    private const STATUS_BLOQUEADOS = ['Autorizada', 'Cancelada', 'Inutilizada'];

    public function index(Request $request)
    {
        $query = NotaFiscal::with([
            'Filial',
            'Pessoa',
            'NaturezaOperacao',
            'Operacao',
        ]);

        // Filtros
        if ($request->filled('codfilial')) {
            $query->where('codfilial', $request->codfilial);
        }

        if ($request->filled('codpessoa')) {
            $query->where('codpessoa', $request->codpessoa);
        }

        if ($request->filled('codnaturezaoperacao')) {
            $query->where('codnaturezaoperacao', $request->codnaturezaoperacao);
        }

        if ($request->filled('emitida')) {
            $emitida = ($request->emitida === 'true' || $request->emitida === true);
            $query->where('emitida', $emitida);
        }

        if ($request->filled('modelo')) {
            $query->where('modelo', $request->modelo);
        }

        if ($request->filled('serie')) {
            $query->where('serie', $request->serie);
        }

        if ($request->filled('numero')) {
            $query->where('numero', $request->numero);
        }

        if ($request->filled('nfechave')) {
            $query->where('nfechave', 'like', '%' . $request->nfechave . '%');
        }

        if ($request->filled('emissao_inicio')) {
            $query->whereDate('emissao', '>=', $request->emissao_inicio);
        }

        if ($request->filled('emissao_fim')) {
            $query->whereDate('emissao', '<=', $request->emissao_fim);
        }

        if ($request->filled('saida_inicio')) {
            $query->whereDate('saida', '>=', $request->saida_inicio);
        }

        if ($request->filled('saida_fim')) {
            $query->whereDate('saida', '<=', $request->saida_fim);
        }

        // Filtro de status
        if ($request->filled('status')) {
            $status = $request->status;

            switch ($status) {
                case 'Autorizada':
                    $query->whereNotNull('nfeautorizacao');
                    break;
                case 'Cancelada':
                    $query->whereNotNull('nfecancelamento');
                    break;
                case 'Inutilizada':
                    $query->whereNotNull('nfeinutilizacao');
                    break;
                case 'Pendente':
                    $query->whereNull('nfeautorizacao')
                        ->whereNull('nfecancelamento')
                        ->whereNull('nfeinutilizacao');
                    break;
            }
        }

        // Ordenação
        $sortField = $request->get('sort', 'saida');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        return NotaFiscalResource::collection(
            $query->paginate($request->get('per_page', 20))
        );
    }

    public function show(int $codnotafiscal)
    {
        return new NotaFiscalDetailResource(
            NotaFiscal::with([
                'Filial',
                'EstoqueLocal',
                'Pessoa',
                'NaturezaOperacao',
                'Operacao',
                'PessoaTransportador',
                'EstadoPlaca',
                'NotaFiscalProdutoBarraS.ProdutoBarra.ProdutoVariacao.Produto',
                'NotaFiscalProdutoBarraS.Cfop',
                'NotaFiscalProdutoBarraS.NotaFiscalItemTributos.Tributo',
                'NotaFiscalPagamentoS',
                'NotaFiscalDuplicatasS',
                'NotaFiscalReferenciadaS',
                'NotaFiscalCartaCorrecaoS',
            ])->findOrFail($codnotafiscal)
        );
    }

    public function store(NotaFiscalRequest $request)
    {
        $nota = NotaFiscal::create($request->validated());

        return (new NotaFiscalDetailResource($nota))
            ->response()
            ->setStatusCode(201);
    }

    public function update(NotaFiscalRequest $request, int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($nota);

        $nota->update($request->validated());

        return new NotaFiscalDetailResource($nota->fresh());
    }

    public function destroy(int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($nota);

        $nota->delete();

        return response()->noContent();
    }

    /**
     * Verifica se a nota está em um status que impede alterações
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function verificarNotaBloqueada(NotaFiscal $nota): void
    {
        $status = NotaFiscalService::getStatusNota($nota);

        if (in_array($status, self::STATUS_BLOQUEADOS)) {
            abort(422, "Não é possível modificar uma nota com status: {$status}");
        }
    }
}
