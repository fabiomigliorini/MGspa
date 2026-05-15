<?php

namespace Mg\Titulo;

use Dompdf\Dompdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Mg\Usuario\Autorizador;

class LiquidacaoTituloController extends Controller
{
    private const GRUPOS_LEITURA = ['Administrador', 'Financeiro', 'Cobranca', 'Gerente', 'Caixa'];
    private const GRUPOS_MUTACAO = ['Administrador', 'Financeiro', 'Cobranca', 'Gerente', 'Caixa'];

    public function index(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);

        $filtros = $request->only([
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
        ]);
        $filtros['filiais_permitidas'] = LiquidacaoTituloAutorizador::filiaisRestritas(Auth::user()->codusuario);

        $paginator = LiquidacaoTituloService::listar($filtros);

        return LiquidacaoTituloListaResource::collection($paginator);
    }

    public function show(int $id)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        $liq = LiquidacaoTituloService::carregar($id);
        if (!LiquidacaoTituloAutorizador::podeVer($liq, Auth::user()->codusuario)) {
            abort(403, 'Liquidação não pertence à sua filial.');
        }
        return new LiquidacaoTituloDetalheResource($liq);
    }

    public function store(LiquidacaoTituloStoreRequest $request)
    {
        Autorizador::autoriza(self::GRUPOS_MUTACAO);

        if (!LiquidacaoTituloAutorizador::podeCriar(Auth::user()->codusuario, (int)$request->codportador)) {
            abort(403, 'Portador não pertence à sua filial.');
        }

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
            $liq = LiquidacaoTitulo::with('Portador')->findOrFail($id);
            $bloqueio = LiquidacaoTituloAutorizador::motivoBloqueioEstorno($liq, Auth::user()->codusuario);
            if ($bloqueio !== null) {
                DB::rollBack();
                abort(403, $bloqueio);
            }
            $liq = LiquidacaoTituloService::estornar($liq);
            DB::commit();
            return new LiquidacaoTituloDetalheResource($liq);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function relatorio(Request $request)
    {
        Autorizador::autoriza(self::GRUPOS_LEITURA);
        $params = $request->all();
        $params['filiais_permitidas'] = LiquidacaoTituloAutorizador::filiaisRestritas(Auth::user()->codusuario);
        if ($request->boolean('html')) {
            $html = LiquidacaoTituloRelatorioService::html($params);
            return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }
        $pdf = LiquidacaoTituloRelatorioService::pdf($params);
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
