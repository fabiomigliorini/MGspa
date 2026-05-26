<?php

namespace Mg\Veiculo;

use Mg\Usuario\Usuario;
use Mg\MgModel;

class VeiculoTipo extends MgModel
{
    protected $table = 'tblveiculotipo';
    protected $primaryKey = 'codveiculotipo';

    protected $fillable = [
        'inativo',
        'reboque',
        'tipocarroceria',
        'tiporodado',
        'tracao',
        'veiculotipo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codveiculotipo' => 'integer',
        'reboque' => 'boolean',
        'tipocarroceria' => 'integer',
        'tiporodado' => 'integer',
        'tracao' => 'boolean',
    ];

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function VeiculoS()
    {
        return $this->hasMany(Veiculo::class, 'codveiculotipo', 'codveiculotipo');
    }
}
