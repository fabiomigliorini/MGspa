<?php

namespace Mg\Ocorrencia;

use Mg\Auditoria\Auditoria;
use Mg\MgModel;

/**
 * Ligacao N:N do livro de ocorrencias com a auditoria (TASK-205): uma
 * ocorrencia amarra as auditorias que o gerente confere, e uma auditoria pode
 * estar em mais de uma ocorrencia.
 */
class OcorrenciaAuditoria extends MgModel
{
    protected $table = 'tblocorrenciaauditoria';
    protected $primaryKey = 'codocorrenciaauditoria';

    protected $fillable = [
        'codauditoria',
        'codocorrencia',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codauditoria' => 'integer',
        'codocorrencia' => 'integer',
        'codocorrenciaauditoria' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
    ];

    // Chaves Estrangeiras
    public function Ocorrencia()
    {
        return $this->belongsTo(Ocorrencia::class, 'codocorrencia', 'codocorrencia');
    }

    public function Auditoria()
    {
        return $this->belongsTo(Auditoria::class, 'codauditoria', 'codauditoria');
    }
}
