<?php

namespace Mg\Pagamento;

use Mpdf\Mpdf;

// Relatorio de pagamentos (M6 doc-3; era o de Liquidacoes): os mesmos
// filtros da listagem unica (M6.1), com os titulos de cada pagamento
class PagamentoRelatorioService
{
    // o PDF nao aguenta o mes inteiro de vendas de todas as filiais
    const LIMITE = 5000;

    public static function pdf(array $filtros): string
    {
        $html = self::html($filtros);

        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 22,
            'margin_bottom' => 14,
            'margin_header' => 5,
            'margin_footer' => 5,
            'default_font' => 'helvetica',
            'tempDir' => $tempDir,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public static function html(array $filtros): string
    {
        ini_set('memory_limit', '512M');
        set_time_limit(120);

        $q = Pagamento::query()
            ->select('tblpagamento.*')
            ->with([
                'Pessoa:codpessoa,fantasia',
                'Negocio:codnegocio,codpessoa',
                'Negocio.Pessoa:codpessoa,fantasia',
                'PortadorDestino:codportador,portador',
                'PortadorOrigem:codportador,portador',
                'UsuarioCriacao:codusuario,usuario',
                'MovimentoTituloS.TipoMovimentoTitulo:codtipomovimentotitulo,tipomovimentotitulo',
                'MovimentoTituloS.Titulo:codtitulo,codpessoa,numero,vencimento',
                'MovimentoTituloS.Titulo.Pessoa:codpessoa,fantasia',
            ]);
        PagamentoListaService::filtrar($q, $filtros);
        if ((clone $q)->count() > static::LIMITE) {
            abort(422, 'Mais de ' . static::LIMITE . ' pagamentos: refine os filtros do relatório.');
        }
        $q->orderBy('tblpagamento.lancamento', 'desc')
            ->orderBy('tblpagamento.codpagamento', 'desc');

        $pags = $q->get();

        $totalPag = 0.0;
        foreach ($pags as $pag) {
            if ($pag->estado != PagamentoService::ESTADO_CANCELADO) {
                $totalPag += PagamentoListaService::valorComSinal($pag);
            }
        }

        return view('pagamento.relatorio', compact('pags', 'totalPag'))->render();
    }
}
