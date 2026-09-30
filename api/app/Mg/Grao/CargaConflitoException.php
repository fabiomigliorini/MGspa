<?php

namespace Mg\Grao;

/**
 * O aparelho mandou uma `versao` diferente da que o servidor tem gravada:
 * outro aparelho sincronizou esta carga no meio do caminho. Nao e erro de
 * sistema — o handler em bootstrap/app.php devolve 409 com a carga atual, e
 * o front resolve o conflito a favor do servidor sem precisar de outro
 * request (ver CargaService::sincronizar e stores/sincronizacao.js).
 */
class CargaConflitoException extends \Exception
{
    public function __construct(public readonly Carga $carga)
    {
        parent::__construct('Esta carga foi alterada em outro aparelho.');
    }
}
