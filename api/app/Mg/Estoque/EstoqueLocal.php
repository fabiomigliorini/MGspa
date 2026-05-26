<?php

namespace Mg\Estoque;

use Mg\Usuario\Usuario;
use Mg\Filial\Filial;
use Mg\MgModel;

class EstoqueLocal extends MgModel
{
    protected $table = 'tblestoquelocal';
    protected $primaryKey = 'codestoquelocal';

    protected $fillable = [
        'codfilial',
        'controlaestoque',
        'deposito',
        'estoquelocal',
        'inativo',
        'sigla',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'codestoquelocal' => 'integer',
        'codfilial' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'controlaestoque' => 'boolean',
        'deposito' => 'boolean',
    ];

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
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
