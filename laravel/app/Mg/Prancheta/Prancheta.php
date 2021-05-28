<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/May/2021 15:22:48
 */

namespace Mg\Prancheta;

use Mg\MgModel;
use Mg\Prancheta\PranchetaProduto;
use Mg\Usuario\Usuario;

class Prancheta extends MgModel
{
    protected $table = 'tblprancheta';
    protected $primaryKey = 'codprancheta';


    protected $fillable = [
        'inativo',
        'observacoes',
        'prancheta'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codprancheta' => 'integer',
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
    public function PranchetaProdutoS()
    {
        return $this->hasMany(PranchetaProduto::class, 'codprancheta', 'codprancheta');
    }

}