<?php

namespace Mg\NotaFiscal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\Filial\Filial;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\NotaFiscal\Requests\NotaFiscalRequest;
use Mg\NotaFiscal\Requests\NotaFiscalStatusRequest;
use Mg\NotaFiscal\Resources\NotaFiscalResource;
use Mg\NotaFiscal\Resources\NotaFiscalDetailResource;
use Mg\NFePHP\NFePHPService;

class NotaFiscalController extends Controller
{
    /**
     * Statuses que impedem modificações na nota
     */
    private const STATUS_BLOQUEADOS = [
        NotaFiscalService::STATUS_AUTORIZADA,
        NotaFiscalService::STATUS_CANCELADA,
        NotaFiscalService::STATUS_INUTILIZADA
    ];

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

        if ($request->filled('codgrupoeconomico')) {
            $query->whereHas('Pessoa', function($q) use ($request) {
                $q->where('codgrupoeconomico', $request->codgrupoeconomico);
            });
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
            $query->where('emissao', '>=', "$request->emissao_inicio 00:00:00");
        }

        if ($request->filled('emissao_fim')) {
            $query->where('emissao', '<=', "$request->emissao_fim 23:59:59");
        }

        if ($request->filled('saida_inicio')) {
            $query->where('saida', '>=', "$request->saida_inicio 00:00:00");
        }

        if ($request->filled('saida_fim')) {
            $query->where('saida', '<=', "$request->saida_fim 23:59:59");
        }

        if ($request->filled('valortotal_inicio')) {
            $query->where('valortotal', '>=', $request->valortotal_inicio);
        }

        if ($request->filled('valortotal_fim')) {
            $query->where('valortotal', '<=', $request->valortotal_fim);
        }

        // Filtro de status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Ordenação
        $sortField = $request->get('sort', 'saida');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);
        $query->orderByDesc('codnotafiscal');

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
                'NotaFiscalProdutoBarraS.NotaFiscalItemTributoS.Tributo',
                'NotaFiscalPagamentoS',
                'NotaFiscalDuplicatasS',
                'NotaFiscalReferenciadaS',
                'NotaFiscalCartaCorrecaoS',
            ])->findOrFail($codnotafiscal)
        );
    }

    public function store(NotaFiscalRequest $request)
    {
        $data = $request->validated();
        $data['codoperacao'] = NaturezaOperacao::findOrFail($data['codnaturezaoperacao'])->codoperacao;
        $data['serie'] = Filial::findOrFail($data['codfilial'])->nfeserie;
        $nota = NotaFiscal::create($data);

        return (new NotaFiscalDetailResource($nota))
            ->response()
            ->setStatusCode(201);
    }

    public function update(NotaFiscalRequest $request, int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($nota);

        $data = $request->validated();
        $data['codoperacao'] = NaturezaOperacao::findOrFail($data['codnaturezaoperacao'])->codoperacao;
        $nota->update($data);

        return new NotaFiscalDetailResource($nota->fresh());
    }

    /**
     * Atualiza apenas o status da nota fiscal
     */
    public function updateStatus(NotaFiscalStatusRequest $request, int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        $validated = $request->validated();

        // Atualiza o status
        $nota->status = $validated['status'];

        // Atualiza os campos opcionais de NFe se foram enviados
        if (array_key_exists('nfeautorizacao', $validated)) {
            $nota->nfeautorizacao = $validated['nfeautorizacao'];
        }
        if (array_key_exists('nfedataautorizacao', $validated)) {
            $nota->nfedataautorizacao = $validated['nfedataautorizacao'];
        }
        if (array_key_exists('nfecancelamento', $validated)) {
            $nota->nfecancelamento = $validated['nfecancelamento'];
        }
        if (array_key_exists('nfedatacancelamento', $validated)) {
            $nota->nfedatacancelamento = $validated['nfedatacancelamento'];
        }
        if (array_key_exists('nfeinutilizacao', $validated)) {
            $nota->nfeinutilizacao = $validated['nfeinutilizacao'];
        }
        if (array_key_exists('nfedatainutilizacao', $validated)) {
            $nota->nfedatainutilizacao = $validated['nfedatainutilizacao'];
        }

        $nota->save();

        return new NotaFiscalDetailResource(
            $nota->fresh([
                'Filial',
                'EstoqueLocal',
                'Pessoa',
                'NaturezaOperacao',
                'Operacao',
                'PessoaTransportador',
                'EstadoPlaca',
                'NotaFiscalProdutoBarraS.ProdutoBarra.ProdutoVariacao.Produto',
                'NotaFiscalProdutoBarraS.Cfop',
                'NotaFiscalProdutoBarraS.NotaFiscalItemTributoS.Tributo',
                'NotaFiscalPagamentoS',
                'NotaFiscalDuplicatasS',
                'NotaFiscalReferenciadaS',
                'NotaFiscalCartaCorrecaoS',
            ])
        );
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
     * Duplica uma nota fiscal
     */
    public function duplicar(int $codnotafiscal)
    {
        // Carrega a nota original com todos os relacionamentos
        $notaOriginal = NotaFiscal::with([
            'NotaFiscalProdutoBarraS.NotaFiscalItemTributoS',
            'NotaFiscalReferenciadaS',
            'NotaFiscalPagamentoS',
            'NotaFiscalDuplicatasS',
        ])->findOrFail($codnotafiscal);

        // duplica nf dentro de uma transação
        DB::beginTransaction();
        $notaDuplicada = NotaFiscalService::duplicar($notaOriginal);
        DB::commit();

        // retorna o resource de nf
        return (new NotaFiscalDetailResource($notaDuplicada))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Verifica se a nota está em um status que impede alterações
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function verificarNotaBloqueada(NotaFiscal $nota): void
    {
        if (in_array($nota->status, self::STATUS_BLOQUEADOS)) {
            abort(422, "Não é possível modificar uma nota com status: {$nota->status}");
        }
    }

    /**
     * Cria o XML da nota fiscal
     */
    public function criar(Request $request, int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);
        $offline = $request->boolean('offline', false);

        $resultado = NFePHPService::criar($nota, $offline);

        // Recarrega a nota e retorna o resource com o resultado da operação
        $notaAtualizada = NotaFiscal::with([
            'Filial',
            'EstoqueLocal',
            'Pessoa',
            'NaturezaOperacao',
            'Operacao',
            'PessoaTransportador',
            'EstadoPlaca',
            'NotaFiscalProdutoBarraS.ProdutoBarra.ProdutoVariacao.Produto',
            'NotaFiscalProdutoBarraS.Cfop',
            'NotaFiscalProdutoBarraS.NotaFiscalItemTributoS.Tributo',
            'NotaFiscalPagamentoS',
            'NotaFiscalDuplicatasS',
            'NotaFiscalReferenciadaS',
            'NotaFiscalCartaCorrecaoS',
        ])->findOrFail($codnotafiscal);

        return response()->json([
            'nota' => new NotaFiscalDetailResource($notaAtualizada),
            'resultado' => $resultado,
        ]);
    }

    /**
     * Envia a nota fiscal de forma síncrona
     */
    public function enviarSincrono(int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        $resultado = NFePHPService::enviarSincrono($nota);

        // Recarrega a nota e retorna o resource com o resultado da operação
        $notaAtualizada = NotaFiscal::with([
            'Filial',
            'EstoqueLocal',
            'Pessoa',
            'NaturezaOperacao',
            'Operacao',
            'PessoaTransportador',
            'EstadoPlaca',
            'NotaFiscalProdutoBarraS.ProdutoBarra.ProdutoVariacao.Produto',
            'NotaFiscalProdutoBarraS.Cfop',
            'NotaFiscalProdutoBarraS.NotaFiscalItemTributoS.Tributo',
            'NotaFiscalPagamentoS',
            'NotaFiscalDuplicatasS',
            'NotaFiscalReferenciadaS',
            'NotaFiscalCartaCorrecaoS',
        ])->findOrFail($codnotafiscal);

        return response()->json([
            'nota' => new NotaFiscalDetailResource($notaAtualizada),
            'resultado' => $resultado,
        ]);
    }

    /**
     * Consulta a nota fiscal na SEFAZ
     */
    public function consultar(int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        $resultado = NFePHPService::consultar($nota);

        // Recarrega a nota e retorna o resource com o resultado da operação
        $notaAtualizada = NotaFiscal::with([
            'Filial',
            'EstoqueLocal',
            'Pessoa',
            'NaturezaOperacao',
            'Operacao',
            'PessoaTransportador',
            'EstadoPlaca',
            'NotaFiscalProdutoBarraS.ProdutoBarra.ProdutoVariacao.Produto',
            'NotaFiscalProdutoBarraS.Cfop',
            'NotaFiscalProdutoBarraS.NotaFiscalItemTributoS.Tributo',
            'NotaFiscalPagamentoS',
            'NotaFiscalDuplicatasS',
            'NotaFiscalReferenciadaS',
            'NotaFiscalCartaCorrecaoS',
        ])->findOrFail($codnotafiscal);

        return response()->json([
            'nota' => new NotaFiscalDetailResource($notaAtualizada),
            'resultado' => $resultado,
        ]);
    }

    /**
     * Cancela a nota fiscal
     */
    public function cancelar(Request $request, int $codnotafiscal)
    {
        $request->validate([
            'justificativa' => 'required|string|min:15',
        ]);

        $nota = NotaFiscal::findOrFail($codnotafiscal);

        $resultado = NFePHPService::cancelar($nota, $request->justificativa);

        // Recarrega a nota e retorna o resource com o resultado da operação
        $notaAtualizada = NotaFiscal::with([
            'Filial',
            'EstoqueLocal',
            'Pessoa',
            'NaturezaOperacao',
            'Operacao',
            'PessoaTransportador',
            'EstadoPlaca',
            'NotaFiscalProdutoBarraS.ProdutoBarra.ProdutoVariacao.Produto',
            'NotaFiscalProdutoBarraS.Cfop',
            'NotaFiscalProdutoBarraS.NotaFiscalItemTributoS.Tributo',
            'NotaFiscalPagamentoS',
            'NotaFiscalDuplicatasS',
            'NotaFiscalReferenciadaS',
            'NotaFiscalCartaCorrecaoS',
        ])->findOrFail($codnotafiscal);

        return response()->json([
            'nota' => new NotaFiscalDetailResource($notaAtualizada),
            'resultado' => $resultado,
        ]);
    }

    /**
     * Inutiliza a nota fiscal
     */
    public function inutilizar(Request $request, int $codnotafiscal)
    {
        $request->validate([
            'justificativa' => 'required|string|min:15',
        ]);

        $nota = NotaFiscal::findOrFail($codnotafiscal);

        $resultado = NFePHPService::inutilizar($nota, $request->justificativa);

        // Recarrega a nota e retorna o resource com o resultado da operação
        $notaAtualizada = NotaFiscal::with([
            'Filial',
            'EstoqueLocal',
            'Pessoa',
            'NaturezaOperacao',
            'Operacao',
            'PessoaTransportador',
            'EstadoPlaca',
            'NotaFiscalProdutoBarraS.ProdutoBarra.ProdutoVariacao.Produto',
            'NotaFiscalProdutoBarraS.Cfop',
            'NotaFiscalProdutoBarraS.NotaFiscalItemTributoS.Tributo',
            'NotaFiscalPagamentoS',
            'NotaFiscalDuplicatasS',
            'NotaFiscalReferenciadaS',
            'NotaFiscalCartaCorrecaoS',
        ])->findOrFail($codnotafiscal);

        return response()->json([
            'nota' => new NotaFiscalDetailResource($notaAtualizada),
            'resultado' => $resultado,
        ]);
    }

    /**
     * Envia a nota fiscal por email
     */
    public function mail(Request $request, int $codnotafiscal)
    {
        $request->validate([
            'destinatario' => 'nullable|email',
        ]);

        $nota = NotaFiscal::findOrFail($codnotafiscal);

        $resultado = \Mg\NFePHP\NFePHPMailService::mail($nota, $request->destinatario);

        return response()->json($resultado, 200);
    }

    /**
     * Gera o DANFE em PDF
     */
    public function danfe(int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        $pdfPath = NFePHPService::danfe($nota);

        return response()->file($pdfPath);
    }

    /**
     * Imprime a nota fiscal
     */
    public function imprimir(Request $request, int $codnotafiscal)
    {
        $request->validate([
            'impressora' => 'nullable|string',
        ]);

        $nota = NotaFiscal::findOrFail($codnotafiscal);

        $resultado = NFePHPService::imprimir($nota, $request->impressora);

        return response()->json($resultado, 200);
    }
}
