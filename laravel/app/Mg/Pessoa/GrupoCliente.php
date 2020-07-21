<?php
/**
 * Created by php artisan gerador:model.
 * Date: 21/Jul/2020 11:56:40
 */

namespace Mg\Pessoa;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class GrupoCliente extends MgModel
{
    protected $table = 'tblgrupocliente';
    protected $primaryKey = 'codgrupocliente';


    protected $fillable = [
        'grupocliente'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codgrupocliente' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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
        return $this->hasMany(Pessoa::class, 'codgrupocliente', 'codgrupocliente');
    }

}