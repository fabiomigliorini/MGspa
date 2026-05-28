<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:30:53
 */

namespace Mg\Veiculo;

use Mg\MgModel;
use Mg\Veiculo\VeiculoConjuntoVeiculo;
use Mg\Usuario\Usuario;

class VeiculoConjunto extends MgModel
{
    protected $table = 'tblveiculoconjunto';
    protected $primaryKey = 'codveiculoconjunto';


    protected $fillable = [
        'inativo',
        'veiculoconjunto'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codveiculoconjunto' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
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
    public function VeiculoConjuntoVeiculoS()
    {
        return $this->hasMany(VeiculoConjuntoVeiculo::class, 'codveiculoconjunto', 'codveiculoconjunto');
    }

}
