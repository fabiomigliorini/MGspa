<?php

namespace Mg\Titulo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class TituloAgrupamentoController extends Controller
{
    private const GRUPOS_LEITURA = ['Administrador', 'Financeiro', 'Cobranca'];
    private const GRUPOS_MUTACAO = ['Administrador', 'Financeiro', 'Cobranca'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);

        $paginator = TituloAgrupamentoService::listar($request->only([
            'codtituloagrupamento',
            'codpessoa',
            'codgrupoeconomico',
            'codgrupocliente',
            'estornado',
            'emissao_de',
            'emissao_ate',
            'criacao_de',
            'criacao_ate',
        ]));

        return TituloAgrupamentoListaResource::collection($paginator);
    }

    public function show(int $id)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        return new TituloAgrupamentoDetalheResource(TituloAgrupamentoService::carregar($id));
    }

    public function store(TituloAgrupamentoStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS_MUTACAO);

        DB::beginTransaction();
        try {
            $ag = TituloAgrupamentoService::criar($request->validated());
            DB::commit();
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }

        // Registra boletos fora da transação (chamada externa ao BB)
        TituloAgrupamentoService::registrarBoletos($ag);

        return new TituloAgrupamentoDetalheResource(TituloAgrupamentoService::carregar($ag->codtituloagrupamento));
    }

    public function update(TituloAgrupamentoUpdateRequest $request, int $id)
    {
        Autorizador::autoriza(self::GRUPOS_MUTACAO);

        DB::beginTransaction();
        try {
            $ag = TituloAgrupamento::findOrFail($id);
            $ag = TituloAgrupamentoService::atualizar($ag, $request->validated());
            DB::commit();
            return new TituloAgrupamentoDetalheResource($ag);
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function estornar(int $id)
    {
        Autorizador::autoriza(self::GRUPOS_MUTACAO);

        DB::beginTransaction();
        try {
            $ag = TituloAgrupamento::findOrFail($id);
            $ag = TituloAgrupamentoService::estornar($ag);
            DB::commit();
            return new TituloAgrupamentoDetalheResource($ag);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function pendentes(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        $regs = TituloAgrupamentoService::pendentes($request->all());
        return response()->json(['data' => $regs]);
    }

    public function relatorio(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        $pdf = TituloAgrupamentoRelatorioService::pdfListagem($request->all());
        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="agrupamentos.pdf"',
        ]);
    }

    public function relatorioPendentes(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        $pdf = TituloAgrupamentoRelatorioService::pdfPendentes($request->all());
        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="agrupamentos-pendentes.pdf"',
        ]);
    }

    public function relatorioDetalhe(int $id)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        $pdf = TituloAgrupamentoRelatorioService::pdfDetalhe($id);
        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="agrupamento-' . $id . '.pdf"',
        ]);
    }

    public function mail(Request $request, int $codtituloagrupamento)
    {
        $dest = $request->destinatario ?? null;
        $ta = TituloAgrupamento::findOrFail($codtituloagrupamento);
        return TituloAgrupamentoMailService::mail($ta, $dest);
    }

    public function gerarNotaFiscal(Request $request, int $id)
    {
        Autorizador::autoriza(self::GRUPOS_MUTACAO);

        $request->validate([
            'modelo' => 'required|integer|in:55,65',
            'todos'  => 'sometimes|boolean',
        ]);

        try {
            $ag = TituloAgrupamentoService::carregar($id);
            $nota = TituloAgrupamentoService::gerarNotaFiscal(
                $ag,
                (int)$request->modelo,
                (bool)$request->boolean('todos'),
            );
            return response()->json([
                'codnotafiscal' => (int)$nota->codnotafiscal,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
