<?php
/**
 * Created by php artisan gerador:model.
 * Date: 09/Dec/2024 11:31:34
 */

namespace Mg\Saurus;

use Mg\MgModel;
use Mg\Saurus\SaurusPedido;
use Mg\Filial\Filial;
use Mg\Pdv\Pdv;
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
        'codpdv',
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
        'codpdv' => 'integer',
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

    public function Pdv()
    {
        return $this->belongsTo(Pdv::class, 'codpdv', 'codpdv');
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

}