<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Jan/2024 15:01:04
 */

namespace Mg\Colaborador;

use Mg\MgModel;
use Mg\Colaborador\ColaboradorCargo;
use Mg\Meta\MetaFilialPessoa;
use Mg\Usuario\Usuario;

class Cargo extends MgModel
{
    protected $table = 'tblcargo';
    protected $primaryKey = 'codcargo';


    protected $fillable = [
        'adicional',
        'cargo',
        'inativo',
        'salario'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'adicional' => 'float',
        'codcargo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'salario' => 'float'
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
    public function ColaboradorCargoS()
    {
        return $this->hasMany(ColaboradorCargo::class, 'codcargo', 'codcargo');
    }

    public function MetaFilialPessoaS()
    {
        return $this->hasMany(MetaFilialPessoa::class, 'codcargo', 'codcargo');
    }

}