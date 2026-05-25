<?php

namespace Mg\Meta;

use Mg\MgModel;

/**
 * Stub do MetaUnidadeNegocio — usado apenas pela validação de FK em
 * UnidadeNegocioController::destroy. Quando o domínio Meta for migrado,
 * substituir por versão completa.
 */
class MetaUnidadeNegocio extends MgModel
{
    protected $table = 'tblmetaunidadenegocio';
    protected $primaryKey = 'codmetaunidadenegocio';

    public $timestamps = false;
}
