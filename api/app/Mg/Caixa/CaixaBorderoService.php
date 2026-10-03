<?php

namespace Mg\Caixa;

use Illuminate\Support\Facades\URL;
use Mg\Portador\PortadorPeriodo;
use Mg\Portador\PortadorPeriodoResource;

/**
 * Bordero do caixa (M9; completo desde o M13 doc-3): a contagem por cedula
 * e moeda, os itens do caixa, avulsos, ajustes e, como informacao, o que os
 * PDVs da gaveta movimentaram fora o dinheiro. Bobina 80mm, como o recibo.
 */
class CaixaBorderoService
{
    public static function pdf(PortadorPeriodo $sessao): string
    {
        $sessao->load(['Portador.Filial.Pessoa', 'UsuarioAbertura', 'UsuarioFechamento']);
        $painel = CaixaService::painel($sessao);
        $informativo = $painel['informativo'];
        // o resumo e a contagem como a tela do periodo mostra
        $periodo = (new PortadorPeriodoResource($sessao))->comLancamentos()->resolve();
        $html = view('caixa.bordero-termica', compact('sessao', 'painel', 'informativo', 'periodo'))->render();
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper([0.0, 0.0, 226.77, 841.89], 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }

    public static function url(PortadorPeriodo $sessao): string
    {
        return URL::temporarySignedRoute('pdv.caixa.bordero', now()->addMinutes(10), ['id' => $sessao->codportadorperiodo]);
    }

    public static function imprimir(PortadorPeriodo $sessao, string $impressora): void
    {
        $url = static::url($sessao);
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages -u "'
            . config('services.ably.key') . '" -H "Content-Type: application/json" --data \'{ "name": "' . $impressora
            . '", "data": "{\"url\": \"' . $url . '\", \"method\": \"get\", \"options\": [], \"copies\": 1}" }\'';
        exec($cmd);
    }
}
