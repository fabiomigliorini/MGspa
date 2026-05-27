<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:40:43
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codfilial' => 'integer',
        'codsauruspdv' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'numero' => 'integer',
        'vencimento' => 'datetime'
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
