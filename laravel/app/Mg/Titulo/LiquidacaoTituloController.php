<?php

namespace Mg\Titulo;

use Dompdf\Dompdf;
use Illuminate\Routing\Controller;
use Mg\Usuario\Autorizador;

class LiquidacaoTituloController extends Controller
{
    public function recibo(int $id)
    {
        Autorizador::autoriza(['Recursos Humanos']);
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
        Autorizador::autoriza(['Recursos Humanos']);
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
        Autorizador::autoriza(['Recursos Humanos']);
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
        $dompdf->setPaper('A5', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '.pdf"',
        ]);
    }
}
