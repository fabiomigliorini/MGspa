<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:21:52
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
        'inativo',
        'numero'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcontacontabil' => 'integer',
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
    public function NaturezaOperacaoS()
    {
        return $this->hasMany(NaturezaOperacao::class, 'codcontacontabil', 'codcontacontabil');
    }

    public function TituloS()
    {
        return $this->hasMany(Titulo::class, 'codcontacontabil', 'codcontacontabil');
    }

}
