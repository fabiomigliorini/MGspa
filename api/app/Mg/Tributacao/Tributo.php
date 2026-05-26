<?php

namespace Mg\Tributacao;

use Mg\Usuario\Usuario;
use Mg\MgModel;

class Tributo extends MgModel
{
    protected $table = 'tbltributo';
    protected $primaryKey = 'codtributo';

    protected $fillable = ['codigo', 'descricao', 'ente'];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'codtributo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
    ];

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function TributacaoRegraS()
    {
        return $this->hasMany(TributacaoRegra::class, 'codtributo', 'codtributo');
    }
}
