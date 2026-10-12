<?php

namespace Mg\Auditoria;

use Mg\MgModel;

/**
 * Auditoria (TASK-204): toda alteracao relevante, num lugar so'. O registro
 * alterado e' tabela + codigo (FK generica); antes/depois so' com os campos
 * relevantes que mudaram. Tipos em AuditoriaService.
 */
class Auditoria extends MgModel
{
    protected $table = 'tblauditoria';
    protected $primaryKey = 'codauditoria';

    protected $fillable = [
        'antes',
        'codigo',
        'depois',
        'justificativa',
        'tabela',
        'tipo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'antes' => 'array',
        'codauditoria' => 'integer',
        'codigo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'depois' => 'array',
        'tipo' => 'integer',
    ];
}
