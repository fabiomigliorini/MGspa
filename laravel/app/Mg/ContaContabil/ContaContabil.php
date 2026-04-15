<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Jun/2020 14:49:45
 */

namespace Mg\ContaContabil;

use Mg\MgModel;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;

class ContaContabil extends MgModel
{
    protected $table = 'tblcontacontabil';
    protected $primaryKey = 'codcontacontabil';


    protected $fillable = [
        'contacontabil',
        'numero',
        'inativo'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codcontacontabil' => 'integer',
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
    public function NaturezaOperacaoS()
    {
        return $this->hasMany(NaturezaOperacao::class, 'codcontacontabil', 'codcontacontabil');
    }

    public function TituloS()
    {
        return $this->hasMany(Titulo::class, 'codcontacontabil', 'codcontacontabil');
    }

}