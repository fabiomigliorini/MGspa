<?php

namespace Mg\Ibpt;

use Mg\MgModel;
use Mg\Cidade\Estado;
use Mg\Usuario\Usuario;

class Ibpt extends MgModel
{
    protected $table = 'tblibpt';
    protected $primaryKey = 'codibpt';

    protected $fillable = [
        'chave',
        'codestado',
        'descricao',
        'estadual',
        'extarif',
        'fonte',
        'importado',
        'municipal',
        'nacional',
        'ncm',
        'tipo',
        'versao',
        'vigenciafim',
        'vigenciainicio'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codestado' => 'integer',
        'codibpt' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'estadual' => 'float',
        'extarif' => 'integer',
        'importado' => 'float',
        'municipal' => 'float',
        'nacional' => 'float',
        'tipo' => 'integer',
        'vigenciafim' => 'date',
        'vigenciainicio' => 'date'
    ];

    // Chaves Estrangeiras
    public function Estado()
    {
        return $this->belongsTo(Estado::class, 'codestado', 'codestado');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }
}
