<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Jun/2020 14:46:10
 */

namespace Mg\Titulo;

use Mg\MgModel;
use Mg\Titulo\MovimentoTitulo;
use Mg\Pessoa\Pessoa;
use Mg\Portador\Portador;
use Mg\Usuario\Usuario;

class LiquidacaoTitulo extends MgModel
{
    protected $table = 'tblliquidacaotitulo';
    protected $primaryKey = 'codliquidacaotitulo';


    protected $fillable = [
        'codpessoa',
        'codportador',
        'codusuario',
        'codusuarioestorno',
        'credito',
        'debito',
        'estornado',
        'observacao',
        'sistema',
        'transacao'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'estornado',
        'sistema',
        'transacao'
    ];

    protected $casts = [
        'codliquidacaotitulo' => 'integer',
        'codpessoa' => 'integer',
        'codportador' => 'integer',
        'codusuario' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codusuarioestorno' => 'integer',
        'credito' => 'float',
        'debito' => 'float'
    ];


    // Chaves Estrangeiras
    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'codusuario', 'codusuario');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioEstorno()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioestorno', 'codusuario');
    }


    // Tabelas Filhas
    public function MovimentoTituloS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codliquidacaotitulo', 'codliquidacaotitulo');
    }

}