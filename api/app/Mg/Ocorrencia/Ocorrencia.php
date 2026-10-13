<?php

namespace Mg\Ocorrencia;

use Mg\Auditoria\Auditoria;
use Mg\Filial\Filial;
use Mg\MgModel;
use Mg\Negocio\Negocio;
use Mg\Pdv\Pdv;
use Mg\Usuario\Usuario;

/**
 * Livro de ocorrencias (TASK-205): o que o gerente confere do PDV
 * monitorado. O fato (antes/depois, justificativa) fica na auditoria; a
 * ocorrencia amarra as auditorias (N:N, tblocorrenciaauditoria) e guarda a
 * descricao, o valor e a conferencia. Codigos em OcorrenciaService.
 */
class Ocorrencia extends MgModel
{
    protected $table = 'tblocorrencia';
    protected $primaryKey = 'codocorrencia';

    protected $fillable = [
        'codfilial',
        'codigo',
        'codnegocio',
        'codpdv',
        'codusuario',
        'codusuarioconferencia',
        'conferencia',
        'criacao',
        'descricao',
        'observacao',
        'tabela',
        'tipo',
        'valor',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codfilial' => 'integer',
        'codigo' => 'integer',
        'codnegocio' => 'integer',
        'codocorrencia' => 'integer',
        'codpdv' => 'integer',
        'codusuario' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuarioconferencia' => 'integer',
        'codusuariocriacao' => 'integer',
        'conferencia' => 'datetime',
        'criacao' => 'datetime',
        'tipo' => 'integer',
        'valor' => 'float',
    ];

    // Chaves Estrangeiras
    public function AuditoriaS()
    {
        return $this->belongsToMany(Auditoria::class, 'tblocorrenciaauditoria', 'codocorrencia', 'codauditoria')
            ->orderBy('tblauditoria.codauditoria');
    }

    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function Pdv()
    {
        return $this->belongsTo(Pdv::class, 'codpdv', 'codpdv');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'codusuario', 'codusuario');
    }

    public function UsuarioConferencia()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioconferencia', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }
}
