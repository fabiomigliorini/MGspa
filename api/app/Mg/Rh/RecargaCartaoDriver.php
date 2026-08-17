<?php

namespace Mg\Rh;

/**
 * Contrato de recarga (reposição de saldo) do cartão-benefício.
 *
 * Duas implementações previstas:
 *  - AcertoPlanilhaCartaoXlsx: gera a planilha CPF|Valor (fluxo atual, manual).
 *  - RecargaApiBee: integração direta com a API da Bee (futuro).
 *
 * O driver opera sobre um LOTE já persistido (BeeRecarga), e a fonte dos
 * valores é sempre BeeRecargaService::linhasDoLote() — assim planilha e API
 * enviam exatamente as mesmas linhas, e o que foi enviado fica gravado.
 */
interface RecargaCartaoDriver
{
    /**
     * Entrega o lote e devolve o artefato — bytes do arquivo na planilha;
     * protocolo/retorno da API nas integrações diretas.
     */
    public function gerarRecarga(BeeRecarga $recarga): string;
}
