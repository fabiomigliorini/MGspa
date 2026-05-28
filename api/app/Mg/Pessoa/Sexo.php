<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 12:27:07
 */

namespace Mg\Pessoa;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class Sexo extends MgModel
{
    protected $table = 'tblsexo';
    protected $primaryKey = 'codsexo';


    protected $fillable = [
        'sexo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codsexo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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


    // Tabelas Filhas
    public function PessoaS()
    {
        return $this->hasMany(Pessoa::class, 'codsexo', 'codsexo');
    }

}
