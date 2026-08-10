<?php

namespace Mg\NFePHP;

use Mg\MgController;

class SefazComunicacaoController extends MgController
{
    /**
     * Download do XML da conversa (request + response), descompactado.
     *
     * 404 quando o arquivo ja expirou: a retencao dos .gz e de 2 anos
     * (nfe-php:limpar-conversas), enquanto o metadado na tabela fica para sempre.
     */
    public function xml(int $id)
    {
        $reg = SefazComunicacao::findOrFail($id);

        if (empty($reg->arquivo)) {
            abort(404, 'Esta comunicação não possui XML arquivado.');
        }

        $path = rtrim(config('mg.paths.nfe_php'), '/') . '/' . $reg->arquivo;
        if (!file_exists($path)) {
            abort(404, 'Arquivo já expirou (retenção de 2 anos) ou não foi localizado.');
        }

        $conteudo = gzdecode(file_get_contents($path));
        if ($conteudo === false) {
            abort(500, 'Falha ao descompactar o arquivo.');
        }

        // .txt/text-plain de proposito: o arquivo nao e XML puro, e o log da conversa
        // (marcadores REQUEST/RESPONSE + cabecalhos HTTP + os dois envelopes SOAP).
        // Servido como application/xml o navegador tenta parsear e morre na 1a linha.
        $nome = "sefaz-{$reg->codsefazcomunicacao}-{$reg->operacao}.txt";

        return response($conteudo, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$nome}\"",
        ]);
    }
}
