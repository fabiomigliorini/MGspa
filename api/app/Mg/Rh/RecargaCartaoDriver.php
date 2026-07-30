<?php

namespace Mg\Rh;

/**
 * Contrato de recarga (reposição de saldo) do cartão-benefício.
 *
 * Duas implementações previstas:
 *  - AcertoPlanilhaCartaoXlsx: gera a planilha CPF|Valor (fluxo atual, manual).
 *  - RecargaApiBee: integração direta com a API da Bee (futuro).
 *
 * A fonte de dados (linhas CPF|Valor) é sempre
 * AcertoRelatorioFolhaPdf::linhasRecargaBee(), garantindo que planilha e
 * API tragam exatamente os mesmos valores da prévia "Recarga Bee".
 */
interface RecargaCartaoDriver
{
    /**
     * Monta a recarga de UMA empresa mãe para o período informado e devolve o
     * artefato pronto para entrega — bytes do arquivo na planilha; protocolo/
     * retorno da API nas integrações diretas.
     */
    public function gerarRecarga(int $codperiodo, int $codempresa): string;
}
