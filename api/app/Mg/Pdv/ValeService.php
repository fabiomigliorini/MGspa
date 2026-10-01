<?php

namespace Mg\Pdv;

use Dompdf\Dompdf;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioParcelaService;
use Mg\Titulo\TituloService;

class ValeService
{
    /**
     * Comprovante termico 80mm dos vales de um negocio.
     *
     * Sao DOIS tipos de vale no mesmo papel, e a diferenca importa:
     *
     * - o vale VENDIDO neste negocio (tblnegociovale) leva escola, aluno,
     *   turma e a lista do kit, porque e' com ela que a familia vai retirar
     *   o material. O titulo dele e' solto (so' tblnegociovale.codtitulo
     *   aponta), entao o loop dos pagamentos abaixo nunca o acha;
     * - o vale que SOBROU de um resgate parcial, ou o credito de uma
     *   devolucao, leva so' o valor -- e' o caminho que ja' rodava e nao
     *   mudou.
     *
     * Os dois saem com o mesmo codigo de barras "VAL{codtitulo}", que e' o
     * que o caixa bipa no wizard Receber.
     *
     * Com $uuid sai so' aquele vale vendido (botao do card do vale), sem os
     * saldos e creditos. Com $codtitulo sai so' aquele contra vale (botao do
     * card do Contra Vale).
     */
    public static function pdf(Negocio $negocio, $uuid = null, $codtitulo = null)
    {
        $generator = new BarcodeGeneratorPNG();
        $comprovantes = static::comprovantes($negocio, $uuid, $codtitulo);

        $barcodes = [];
        foreach ($comprovantes as $comp) {
            $tit = $comp['titulo'];
            $str = 'VAL' . str_pad($tit->codtitulo, 8, '0', STR_PAD_LEFT);
            $barcodes[$tit->codtitulo] = base64_encode($generator->getBarcode($str, $generator::TYPE_CODE_128, 1, 60));
        }

        // carrega HTML da view
        $dompdf = new Dompdf();
        $html = view('negocio.vale', compact('comprovantes', 'barcodes'))->render();
        $dompdf->loadHtml($html, 'UTF-8');

        // Bobina 80mm x 297 (altura A4)
        $dompdf->setPaper([0.0, 0.0, 226.77, 841.89], 'portrait');

        // Renderiza
        $dompdf->render();

        // retorna o PDF em uma variavel
        return $dompdf->output();
    }

    /**
     * Vales vendidos + saldos de vale resgatado/credito de devolucao que
     * saem no comprovante, indexados por codtitulo (mesmos filtros do pdf).
     */
    public static function comprovantes(Negocio $negocio, $uuid = null, $codtitulo = null)
    {
        // vales vendidos neste negocio
        $comprovantes = [];
        foreach (PdvNegocioValeService::valesAtivos($negocio) as $i => $vale) {
            if (empty($vale->codtitulo) || empty($vale->Titulo)) {
                continue;
            }
            if (($uuid && $vale->uuid != $uuid) || $codtitulo) {
                continue;
            }
            $comprovantes[$vale->codtitulo] = [
                'titulo' => $vale->Titulo,
                'vale' => $vale,
                'letra' => PdvNegocioValeService::letra($i),
            ];
        }

        // saldo de vale resgatado e credito de devolucao (caminho de sempre)
        // $codtitulo filtra por titulo, e nao pelo pagamento: o credito da
        // devolucao e' o titulo da parcela (condicao V)
        foreach ($uuid ? [] : $negocio->PagamentoS as $nfp) {
            if (!empty($nfp->codtitulo) && (!$codtitulo || $nfp->codtitulo == $codtitulo)) {
                if ($nfp->Titulo->codtipotitulo == TituloService::TIPO_VALE && $nfp->Titulo->saldo < 0) {
                    $comprovantes[$nfp->codtitulo] = $comprovantes[$nfp->codtitulo] ?? [
                        'titulo' => $nfp->Titulo,
                        'vale' => null,
                        'letra' => null,
                    ];
                }
            }
        }
        foreach ($uuid ? [] : NegocioParcelaService::titulos($negocio) as $tit) {
            if ($codtitulo && $tit->codtitulo != $codtitulo) {
                continue;
            }
            if ($tit->codtipotitulo == TituloService::TIPO_VALE && $tit->saldo < 0) {
                $comprovantes[$tit->codtitulo] = $comprovantes[$tit->codtitulo] ?? [
                    'titulo' => $tit,
                    'vale' => null,
                    'letra' => null,
                ];
            }
        }

        return $comprovantes;
    }

    public static function imprimir($codnegocio, $impressora, $uuid = null, $codtitulo = null)
    {
        // Executa comando de impressao
        $params = ['codnegocio' => $codnegocio];
        if ($uuid) {
            $params['uuid'] = $uuid;
        }
        if ($codtitulo) {
            $params['codtitulo'] = $codtitulo;
        }
        $url = \URL::temporarySignedRoute('pdv.negocio.vale', now()->addMinutes(10), $params);
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages -u "'
            . config('services.ably.key') . '" -H "Content-Type: application/json" --data \'{ "name": "' . $impressora
            . '", "data": "{\"url\": \"' . $url . '\", \"method\": \"get\", \"options\": [], \"copies\": 1}" }\'';
        exec($cmd);
    }
}
