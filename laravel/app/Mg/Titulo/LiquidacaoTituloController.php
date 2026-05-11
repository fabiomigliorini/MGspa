<?php

namespace Mg\Titulo;

use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class LiquidacaoTituloController extends Controller
{
    private const GRUPOS_LEITURA = ['Administrador', 'Financeiro', 'Cobranca'];
    private const GRUPOS_MUTACAO = ['Administrador', 'Financeiro', 'Cobranca'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);

        $paginator = LiquidacaoTituloService::listar($request->only([
            'codliquidacaotitulo',
            'codpessoa',
            'codgrupoeconomico',
            'codportador',
            'codusuariocriacao',
            'estornado',
            'criacao_de',
            'criacao_ate',
            'transacao_de',
            'transacao_ate',
        ]));

        return LiquidacaoTituloListaResource::collection($paginator);
    }

    public function show(int $id)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        return new LiquidacaoTituloDetalheResource(LiquidacaoTituloService::carregar($id));
    }

    public function store(LiquidacaoTituloStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS_MUTACAO);

        DB::beginTransaction();
        try {
            $liq = LiquidacaoTituloService::criar($request->validated());
            DB::commit();
            return new LiquidacaoTituloDetalheResource($liq);
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
            $liq = LiquidacaoTitulo::findOrFail($id);
            $liq = LiquidacaoTituloService::estornar($liq);
            DB::commit();
            return new LiquidacaoTituloDetalheResource($liq);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function relatorio(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        if ($request->boolean('html')) {
            $html = LiquidacaoTituloRelatorioService::html($request->all());
            return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }
        $pdf = LiquidacaoTituloRelatorioService::pdf($request->all());
        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="liquidacoes.pdf"',
        ]);
    }

    // ==== Recibos (mantidos do código antigo) ====

    public function recibo(int $id)
    {
        Autorizador::autoriza(['Recursos Humanos', 'Financeiro', 'Administrador', 'Cobranca']);
        $liq = static::carregarLiquidacao($id);

        $html = '';
        if ($liq->credito > 0) {
            $html .= view('liquidacao-titulo.recibo-recebimento', compact('liq'))->render();
        }
        if ($liq->debito > 0) {
            $html .= view('liquidacao-titulo.recibo-pagamento', compact('liq'))->render();
        }

        if (empty($html)) {
            throw new \Exception('Liquidação sem valores para recibo.', 400);
        }

        return static::pdfResponse($html, 'recibo-' . $id);
    }

    public function reciboRecebimento(int $id)
    {
        Autorizador::autoriza(['Recursos Humanos', 'Financeiro', 'Administrador', 'Cobranca']);
        $liq = static::carregarLiquidacao($id);

        if ($liq->credito <= 0) {
            throw new \Exception('Liquidação sem créditos para recibo de recebimento.', 400);
        }

        return static::pdfResponse(
            view('liquidacao-titulo.recibo-recebimento', compact('liq'))->render(),
            'recibo-recebimento-' . $id
        );
    }

    public function reciboPagamento(int $id)
    {
        Autorizador::autoriza(['Recursos Humanos', 'Financeiro', 'Administrador', 'Cobranca']);
        $liq = static::carregarLiquidacao($id);

        if ($liq->debito <= 0) {
            throw new \Exception('Liquidação sem débitos para recibo de pagamento.', 400);
        }

        return static::pdfResponse(
            view('liquidacao-titulo.recibo-pagamento', compact('liq'))->render(),
            'recibo-pagamento-' . $id
        );
    }

    protected static function carregarLiquidacao(int $id): LiquidacaoTitulo
    {
        $liq = LiquidacaoTitulo::with([
            'MovimentoTituloS.Titulo.Filial.Pessoa.Cidade.Estado',
            'MovimentoTituloS.Titulo.PeriodoColaboradorS.ColaboradorRubricaS',
            'Pessoa.Cidade.Estado',
            'UsuarioCriacao',
        ])->findOrFail($id);

        if (!empty($liq->estornado)) {
            throw new \Exception('Liquidação estornada.', 400);
        }

        return $liq;
    }

    protected static function pdfResponse(string $html, string $filename)
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
        ]);
    }
}
