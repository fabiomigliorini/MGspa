<?php

namespace Mg\NFePHP;

use Illuminate\Support\Facades\Log;

use Mg\Filial\Filial;

use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;

class NFePHPManifestacaoService
{
    /**
     * Manifestacao do destinatario sobre documento de TERCEIRO.
     *
     * O XML resultante e guardado na arvore do dfe/ (e nao em nfe/) porque a chave e de
     * terceiro: e evento que nos emitimos sobre um documento que recebemos, nao sobre um
     * documento nosso. Antes deste metodo o procEventoNFe era simplesmente descartado.
     */
    public static function manifestacao(Filial $filial, $chNFe, $tpEvento, string $justificativa, $nSeqEvento)
    {
        $tools = NFePHPConfigService::instanciaTools($filial);
        $tools->model(55);
        //este serviço somente opera em ambiente de produção
        // $tools->setEnvironment($filial->nfeambiente);
        $tools->setEnvironment(1);
        $response = NFePHPService::chamarSefazComRetry(
            fn() => $tools->sefazManifesta($chNFe, $tpEvento, $justificativa, $nSeqEvento),
            'manifesta',
            $tools,
            $filial
        );
        $st = (new Standardize($response))->toStd();

        // Guarda o procEventoNFe. Best-effort: nao pode derrubar a manifestacao, que ja
        // foi registrada na SEFAZ neste ponto.
        try {
            $xml = Complements::toAuthorize($tools->lastRequest, $response);
            $path = NFePHPPathService::pathManifestacao(
                $filial,
                $chNFe,
                (int) $tpEvento,
                (int) $nSeqEvento,
                null,
                true
            );
            file_put_contents($path, $xml);
        } catch (\Throwable $e) {
            Log::warning("Manifestacao {$chNFe}: falha ao gravar XML: " . $e->getMessage());
        }

        return $st;
    }
}
