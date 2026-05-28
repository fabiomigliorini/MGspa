<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:30:59
 */

namespace Mg\Veiculo;

use Mg\MgModel;
use Mg\Usuario\Usuario;
use Mg\Veiculo\Veiculo;
use Mg\Veiculo\VeiculoConjunto;

class VeiculoConjuntoVeiculo extends MgModel
{
    protected $table = 'tblveiculoconjuntoveiculo';
    protected $primaryKey = 'codveiculoconjuntoveiculo';


    protected $fillable = [
        'codveiculo',
        'codveiculoconjunto'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codveiculo' => 'integer',
        'codveiculoconjunto' => 'integer',
        'codveiculoconjuntoveiculo' => 'integer',
        'criacao' => 'datetime'
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

    public function Veiculo()
    {
        return $this->belongsTo(Veiculo::class, 'codveiculo', 'codveiculo');
    }

    public function VeiculoConjunto()
    {
        return $this->belongsTo(VeiculoConjunto::class, 'codveiculoconjunto', 'codveiculoconjunto');
    }

}
