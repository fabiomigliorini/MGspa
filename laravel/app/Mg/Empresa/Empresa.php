<?php

namespace Mg\Empresa;

use Mg\MgModel;
use Mg\Usuario\Usuario;
use Mg\Filial\Filial;

class Empresa extends MgModel
{
    const MODOEMISSAONFCE_NORMAL = 1;
    const MODOEMISSAONFCE_OFFLINE = 9;

    protected $table = 'tblempresa';
    protected $primaryKey = 'codempresa';
    protected $fillable = [
        'empresa',
        'modoemissaonfce',
        'contingenciadata',
        'contingenciajustificativa',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
        'contingenciadata',
    ];

    // Chaves Estrangeiras
    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    // Tabelas Filhas
    public function FilialS()
    {
        return $this->hasMany(Filial::class, 'codempresa', 'codempresa');
    }
}
