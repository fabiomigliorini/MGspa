<?php
/**
 * Created by php artisan gerador:model.
 * Date: 26/Jan/2021 08:32:50
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
        'veiculoconjunto'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codveiculoconjunto' => 'integer'
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