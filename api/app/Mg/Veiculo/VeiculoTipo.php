<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:30:41
 */

namespace Mg\Veiculo;

use Mg\MgModel;
use Mg\Veiculo\Veiculo;
use Mg\Usuario\Usuario;

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
        'veiculotipo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codveiculotipo' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'reboque' => 'boolean',
        'tipocarroceria' => 'integer',
        'tiporodado' => 'integer',
        'tracao' => 'boolean'
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
    public function VeiculoS()
    {
        return $this->hasMany(Veiculo::class, 'codveiculotipo', 'codveiculotipo');
    }

}
