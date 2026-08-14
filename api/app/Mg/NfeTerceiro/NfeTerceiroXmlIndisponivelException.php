<?php

namespace Mg\NfeTerceiro;

use Exception;
use Throwable;

/**
 * XML completo (procNFe) de uma NFe de terceiro nao pode ser servido.
 *
 * Nao e "nota nao encontrada": a NfeTerceiro existe e esta no banco. O que falta e uma
 * PRE-CONDICAO — manifestacao do destinatario, ou o documento ainda nao disponivel na
 * SEFAZ. Por isso o handler em bootstrap/app.php devolve 409, nao 404.
 *
 * A $sugestao e a acao concreta que o usuario pode tomar (ex. "manifeste a nota"), exibida
 * em destaque. O $cstat, quando veio da SEFAZ, fica acessivel para log e para testes.
 */
class NfeTerceiroXmlIndisponivelException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?string $sugestao = null,
        public readonly ?string $cstat = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
