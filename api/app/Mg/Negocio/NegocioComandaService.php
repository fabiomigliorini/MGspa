<?php

namespace Mg\Negocio;

use Illuminate\Support\Str;

use JasperPHP\core\Instructions;
use JasperPHP\elements\Report;
use JasperPHP\processors\PdfProcessor;
use Mg\Pdv\Pdv;

class NegocioComandaService
{
    public static function pdf(Negocio $negocio)
    {
        $data = [new NegocioComanda($negocio)];
        $report = new Report(
            app_path('/Mg/Negocio/comanda.jrxml'),
            [],
            null,
            false,
            ['type' => 'array', 'data' => $data],
        );
        Instructions::prepare($report);
        $report->generate();
        $report->out();
        $pdfProcessor = PdfProcessor::get();
        $pdf = $pdfProcessor->Output('comanda.pdf', 'S');
        return $pdf;
    }

    public static function imprimir(Negocio $negocio, $impressora)
    {
        $url = \URL::temporarySignedRoute('negocio.comanda', now()->addMinutes(10), ['codnegocio' => $negocio->codnegocio]);
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages -u "' . env('ABLY_APP_KEY') . '" -H "Content-Type: application/json" --data \'{ "name": "' . $impressora . '", "data": "{\"url\": \"' . $url . '\", \"method\": \"get\", \"options\": [\"fit-to-page\"], \"copies\": 1}" }\'';
        exec($cmd);
    }

    public static function unificar(Negocio $negocio, Negocio $negocioComanda, Pdv $pdv = null)
    {
        // verifica se nao está tentando unificar a comanda nela mesma
        if ($negocioComanda->codnegocio == $negocio->codnegocio) {
            throw new \Exception("Negócio e Comanda são o mesmo código!", 1);
        }

        // verifica se a comanda está aberta
        if ($negocioComanda->codnegociostatus != 1) {
            throw new \Exception("Comanda não está mais aberta!", 1);
        }

        // verifica se o negocio "destino" está aberto
        if ($negocio->codnegociostatus != 1) {
            throw new \Exception("Negócio não está aberto!", 1);
        }

        // verifica se nao está tentando unificar a comanda nela mesma
        if ($negocioComanda->codfilial != $negocio->codfilial) {
            throw new \Exception("Negócio e Comanda são de Filiais diferentes!", 1);
        }


        // verifica se tem item pra "puxar"
        if ($negocioComanda->NegocioProdutoBarras()->count() == 0) {
            throw new \Exception("Comanda não tem nenhum item!", 1);
        }

        // novo PDV ao qual o negocio sera associado
        $codpdv = $pdv->codpdv ?? null;

        // se o negocio "destino" não tem nenhum item, "inverte" os papeis
        // a "comanda" vira o negocio "destino"
        if ($negocio->NegocioProdutoBarras()->count() == 0) {
            // puxa pro usuario
            $negocioComanda->update([
                'codusuario' => $negocio->codusuario,
                'codpdv' => $codpdv,
            ]);
            $negocioComanda->fresh();
            return $negocioComanda;
        }

        // duplica os itens da comanda pro destino
        foreach ($negocioComanda->NegocioProdutoBarras as $pbComanda) {
            if (!empty($pbComanda->inativo)) {
                continue;
            }
            $pb = $pbComanda->replicate();
            $pb->codnegocio = $negocio->codnegocio;
            $pb->uuid = Str::uuid();
            $pb->save();
            if ($codpdv != null) {
                $negocio->valorprodutos += $pbComanda->valorprodutos;
                $negocio->valordesconto += $pbComanda->valordesconto;
                $negocio->valorfrete += $pbComanda->valorfrete;
                $negocio->valorseguro += $pbComanda->valorseguro;
                $negocio->valoroutras += $pbComanda->valoroutras;
                $negocio->valortotal += $pbComanda->valortotal;
            }
        }
        if ($codpdv != null) {
            $negocio->save();
            NegocioService::recalcularTotal($negocio);
        }

        // monta observacoes
        $observacoes = [];
        if (!empty($negocioComanda->observacoes)) {
            $observacoes[] = $negocioComanda->observacoes;
        }
        $observacoes[] = 'Unificado no negócio #' . $negocio->codnegocio;
        $observacoes = implode(" - ", $observacoes);

        // marca a comanda como cancelada
        $negocioComanda->update([
            'codnegociostatus' => 3,
            'observacoes' => $observacoes
        ]);

        // Codigo Legado da Versao antiga Yii
        // pode deixar de fazer depois que for desativado
        // na versao nova desconto e frete já estão nos itens
        if ($codpdv == null) {
            // junta desconto
            if (!empty($negocioComanda->valordesconto)) {
                $negocio->update([
                    'valordesconto' => $negocio->valordesconto + $negocioComanda->valordesconto
                ]);
            }
            // junta frete
            if (!empty($negocioComanda->valorfrete)) {
                $negocio->update([
                    'valorfrete' => $negocio->valorfrete + $negocioComanda->valorfrete
                ]);
            }
        }

        return $negocio->fresh();
    }
}
