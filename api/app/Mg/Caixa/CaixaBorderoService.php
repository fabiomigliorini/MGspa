<?php

namespace Mg\Caixa;

use Illuminate\Support\Facades\URL;
use Mg\Portador\PortadorPeriodo;

/**
 * Bordero do caixa (M9 doc-3): o papel que o caixa leva ao escritorio com o
 * dinheiro. Traz a contagem dele e, como informacao, o que os PDVs da gaveta
 * movimentaram fora o dinheiro. Nao traz o dinheiro do sistema nem a
 * diferenca: o gerente confere as cegas. Bobina 80mm, como o recibo.
 */
class CaixaBorderoService
{
    public static function pdf(PortadorPeriodo $sessao): string
    {
        $sessao->load(['Portador.Filial.Pessoa', 'UsuarioAbertura', 'UsuarioFechamento']);
        $informativo = CaixaService::informativo($sessao);
        $html = view('caixa.bordero-termica', compact('sessao', 'informativo'))->render();
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
