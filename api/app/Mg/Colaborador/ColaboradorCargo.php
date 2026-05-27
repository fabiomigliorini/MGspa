<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:37:25
 */

namespace Mg\Colaborador;

use Mg\MgModel;
use Mg\Colaborador\Cargo;
use Mg\Colaborador\Colaborador;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;

class ColaboradorCargo extends MgModel
{
    protected $table = 'tblcolaboradorcargo';
    protected $primaryKey = 'codcolaboradorcargo';


    protected $fillable = [
        'codcargo',
        'codcolaborador',
        'fim',
        'inicio',
        'observacoes',
        'salario'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcargo' => 'integer',
        'codcolaborador' => 'integer',
        'codcolaboradorcargo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'fim' => 'date',
        'inicio' => 'date',
        'salario' => 'float'
    ];


    // Chaves Estrangeiras
    public function Cargo()
    {
        return $this->belongsTo(Cargo::class, 'codcargo', 'codcargo');
    }

    public function Colaborador()
    {
        return $this->belongsTo(Colaborador::class, 'codcolaborador', 'codcolaborador');
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
