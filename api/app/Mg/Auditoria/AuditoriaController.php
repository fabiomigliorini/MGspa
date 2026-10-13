<?php

namespace Mg\Auditoria;

use Illuminate\Routing\Controller;

/**
 * Historico de um registro (TASK-209): o MgHistorico do front passa a tabela
 * e o codigo. Qualquer usuario ve.
 */
class AuditoriaController extends Controller
{
    public function historico(string $tabela, int $codigo)
    {
        return AuditoriaService::historico($tabela, $codigo);
    }
}
