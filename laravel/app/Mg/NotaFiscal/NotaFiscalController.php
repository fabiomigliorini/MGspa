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
use Mg\NotaFiscal\NotaFiscalDevolucaoService;

class NotaFiscalController extends Controller
{

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
            $query->whereHas('Pessoa', function ($q) use ($request) {
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

        // Captura valores antes da alteração para verificar se houve mudança
        $valoresAntigos = [
            'valordesconto' => $nota->valordesconto,
            'valorfrete' => $nota->valorfrete,
            'valorseguro' => $nota->valorseguro,
            'valoroutras' => $nota->valoroutras,
        ];

        $data = $request->validated();
        $data['codoperacao'] = NaturezaOperacao::findOrFail($data['codnaturezaoperacao'])->codoperacao;

        DB::beginTransaction();
        $nota->update($data);

        // Rateia valores entre os itens se algum valor foi alterado
        NotaFiscalItemService::ratearValoresItens($nota, $valoresAntigos);
        DB::commit();

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

        DB::beginTransaction();
        foreach ($nota->NfeTerceiroS as $ter) {
            $ter->codnotafiscal = null;
            $ter->save();
        }
        $nota->delete();
        DB::commit();

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
     * Gera uma nota fiscal de devolução parcial
     */
    public function devolucao(Request $request, int $codnotafiscal)
    {
        // Carrega a nota original com todos os relacionamentos
        $notaOriginal = NotaFiscal::with([
            'NotaFiscalProdutoBarraS.NotaFiscalItemTributoS',
        ])->findOrFail($codnotafiscal);

        // Validação condicional: codpessoa obrigatório se nota original for Consumidor (1)
        $rules = [
            'itens' => 'required|array|min:1',
            'itens.*.codnotafiscalprodutobarra' => 'required|integer',
            'itens.*.quantidade' => 'required|numeric|min:0.001',
        ];

        if ($notaOriginal->codpessoa == 1) {
            $rules['codpessoa'] = 'required|integer|min:2';
        }

        $request->validate($rules);

        // Define o codpessoa a ser usado na devolução
        $codpessoa = $notaOriginal->codpessoa == 1
            ? $request->codpessoa
            : $notaOriginal->codpessoa;

        // Gera a nota de devolução dentro de uma transação
        DB::beginTransaction();
        $notaDevolucao = NotaFiscalDevolucaoService::gerarDevolucao($notaOriginal, $request->itens, $codpessoa);
        DB::commit();

        // retorna o resource de nf
        return (new NotaFiscalDetailResource($notaDevolucao))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Lista notas fiscais que podem ser unificadas com a nota informada
     */
    public function listarParaUnificar(int $codnotafiscal)
    {
        $nota = NotaFiscal::with(['Filial', 'NaturezaOperacao'])->findOrFail($codnotafiscal);

        // Só pode unificar notas em digitação
        if ($nota->status !== NotaFiscalStatusService::STATUS_DIGITACAO) {
            abort(422, "Só é possível unificar notas em digitação");
        }

        // Busca notas com mesmo status, natureza e pessoa (exceto a própria nota)
        $notas = NotaFiscal::with(['Filial', 'NaturezaOperacao'])
            ->where('status', NotaFiscalStatusService::STATUS_DIGITACAO)
            ->where('codnaturezaoperacao', $nota->codnaturezaoperacao)
            ->where('codpessoa', $nota->codpessoa)
            ->where('codfilial', $nota->codfilial)
            ->where('codnotafiscal', '!=', $codnotafiscal)
            ->orderBy('emissao', 'desc')
            ->get();

        return response()->json($notas->map(function ($nf) {
            return [
                'codnotafiscal' => $nf->codnotafiscal,
                'codfilial' => $nf->codfilial,
                'filial' => $nf->Filial->filial ?? null,
                'natureza' => $nf->NaturezaOperacao->naturezaoperacao ?? null,
                'emissao' => $nf->emissao,
                'valortotal' => $nf->valortotal,
            ];
        }));
    }

    /**
     * Unifica notas fiscais na nota informada
     */
    public function unificar(Request $request, int $codnotafiscal)
    {
        $request->validate([
            '*.codnotafiscal' => 'required|integer',
        ]);

        $nota = NotaFiscal::findOrFail($codnotafiscal);

        // Só pode unificar notas em digitação
        if ($nota->status !== NotaFiscalStatusService::STATUS_DIGITACAO) {
            abort(422, "Só é possível unificar notas em digitação");
        }

        // Extrai os códigos das notas a serem unificadas
        $codigosNotas = collect($request->all())->pluck('codnotafiscal')->toArray();

        // Unifica dentro de uma transação
        DB::beginTransaction();
        $notaUnificada = NotaFiscalService::unificarNotas($nota, $codigosNotas);
        DB::commit();

        return new NotaFiscalDetailResource($notaUnificada);
    }

    /**
     * Verifica se a nota está em um status que impede alterações
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function verificarNotaBloqueada(NotaFiscal $nota): void
    {
        if (!NotaFiscalStatusService::isEditable($nota)) {
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

    /**
     * Retorna o XML da nota fiscal
     */
    public function xml(int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        $xml = NFePHPService::xml($nota);

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }

    /**
     * Recalcula a tributação de todos os itens da nota fiscal
     */
    public function recalcularTributacao(int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        // Só pode recalcular nota em digitação
        if ($nota->status !== NotaFiscalStatusService::STATUS_DIGITACAO) {
            abort(422, "Só é possível recalcular tributação de notas em digitação");
        }

        DB::beginTransaction();
        NotaFiscalItemService::recalcularTributacao($nota);
        DB::commit();

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

    /**
     * Incorpora os valores de frete, seguro, desconto e outras no valor unitário dos itens
     */
    public function incorporarValores(int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($nota);

        DB::beginTransaction();
        NotaFiscalItemService::incorporarValores($nota);
        DB::commit();

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

    /**
     * Gera o espelho da nota fiscal em PDF
     */
    public function espelho(int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        $pdf = NotaFiscalEspelhoPdfService::pdf($nota);

        return response()->make($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Espelho-NF-' . $nota->numero . '.pdf"'
        ]);
    }

    /**
     * Unifica itens da nota fiscal (agrupa itens com mesmo produto)
     */
    public function unificarItens(int $codnotafiscal)
    {
        $nota = NotaFiscal::findOrFail($codnotafiscal);

        // Verifica se a nota está bloqueada
        $this->verificarNotaBloqueada($nota);

        DB::beginTransaction();
        NotaFiscalItemService::unificarItens($nota);
        DB::commit();

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

    /**
     * Envia carta de correção para a SEFAZ
     */
    public function cartaCorrecao(Request $request, int $codnotafiscal)
    {
        $request->validate([
            'texto' => 'required|string|min:15',
        ]);

        $nota = NotaFiscal::findOrFail($codnotafiscal);

        $resultado = NFePHPService::cartaCorrecao($nota, $request->texto);

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
}
