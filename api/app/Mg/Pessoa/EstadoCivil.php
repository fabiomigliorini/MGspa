<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:28:32
 */

namespace Mg\Pessoa;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class EstadoCivil extends MgModel
{
    protected $table = 'tblestadocivil';
    protected $primaryKey = 'codestadocivil';


    protected $fillable = [
        'estadocivil',
        'inativo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codestadocivil' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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
    public function PessoaS()
    {
        return $this->hasMany(Pessoa::class, 'codestadocivil', 'codestadocivil');
    }

}
