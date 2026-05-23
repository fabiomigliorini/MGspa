<?php

namespace Mg\Rh;

use Mg\MgModel;

/**
 * Stub do PeriodoColaboradorSetor — usado apenas pela validação de FK em
 * SetorController::destroy. Quando o domínio Rh for migrado, substituir
 * por versão completa.
 */
class PeriodoColaboradorSetor extends MgModel
{
    protected $table = 'tblperiodocolaboradorsetor';
    protected $primaryKey = 'codperiodocolaboradorsetor';

    public $timestamps = false;
}
