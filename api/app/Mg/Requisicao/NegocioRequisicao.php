<?php

namespace Mg\Requisicao;

use Mg\MgModel;
use Mg\Negocio\Negocio;
use Mg\Usuario\Usuario;

// Uma requisicao atendida por um negocio de transferencia do PDV (TASK-55, doc-6).
// A lista vem do PDV junto com o negocio (pelo uuid); inativo = tirada do negocio.
class NegocioRequisicao extends MgModel
{
    protected $table = 'tblnegociorequisicao';
    protected $primaryKey = 'codnegociorequisicao';

    protected $fillable = [
        'uuid',
        'codnegocio',
        'codrequisicao',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codnegocio' => 'integer',
        'codnegociorequisicao' => 'integer',
        'codrequisicao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
    ];

    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function Requisicao()
    {
        return $this->belongsTo(Requisicao::class, 'codrequisicao', 'codrequisicao');
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
