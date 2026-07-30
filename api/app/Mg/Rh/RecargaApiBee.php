<?php

namespace Mg\Rh;

/**
 * STUB — Integração direta com a API da Bee (cartão-benefício).
 *
 * TODO: implementar. Pendências / requisitos conhecidos:
 *   - Credenciais Bee: usuário + token de API (guardar em config/env, NÃO no
 *     código; lembrar que credenciais somem em prod — ver rh-google-calendar-sync).
 *   - Cadastro de beneficiário por CPF (garantir que exista antes de recarregar).
 *   - Geração do pagamento/recarga em lote e retorno do protocolo.
 *
 * A fonte dos valores continua sendo
 * AcertoRelatorioFolhaPdf::linhasRecargaBee($codperiodo, $codempresa)
 * (mesmo layout CPF|Valor já usado por AcertoPlanilhaCartaoXlsx).
 */
class RecargaApiBee implements RecargaCartaoDriver
{
    public function gerarRecarga(int $codperiodo, int $codempresa): string
    {
        // TODO: autenticar (usuário + token) → cadastrar beneficiários por CPF →
        //       gerar recarga em lote a partir de
        //       AcertoRelatorioFolhaPdf::linhasRecargaBee($codperiodo, $codempresa)
        //       → devolver o protocolo/retorno da API.
        throw new \RuntimeException(
            'Integração Bee ainda não implementada — pendente de credenciais (usuário/token) da Bee.'
        );
    }
}
