<?php
/**
 * Created by php artisan gerador:model.
 * Date: 25/Jan/2025 11:02:45
 */

namespace Mg\Saurus;

use Mg\MgModel;
use Mg\Saurus\SaurusPedido;
use Mg\Saurus\SaurusPinPad;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;

class SaurusPdv extends MgModel
{
    protected $table = 'tblsauruspdv';
    protected $primaryKey = 'codsauruspdv';


    protected $fillable = [
        'apelido',
        'autorizacao',
        'chavepublica',
        'codfilial',
        'contratoid',
        'id',
        'inativo',
        'numero',
        'vencimento'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
        'vencimento'
    ];

    protected $casts = [
        'codfilial' => 'integer',
        'codsauruspdv' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'numero' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function SaurusPedidoS()
    {
        return $this->hasMany(SaurusPedido::class, 'codsauruspdv', 'codsauruspdv');
    }

    public function SaurusPinPadS()
    {
        return $this->hasMany(SaurusPinPad::class, 'codsauruspdv', 'codsauruspdv');
    }

}