<?php
/**
 * Created by php artisan gerador:model.
 * Date: 25/Jan/2025 11:03:00
 */

namespace Mg\Saurus;

use Mg\MgModel;
use Mg\Saurus\SaurusPagamento;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Negocio\Negocio;
use Mg\Saurus\SaurusPdv;
use Mg\Usuario\Usuario;

class SaurusPedido extends MgModel
{
    protected $table = 'tblsauruspedido';
    protected $primaryKey = 'codsauruspedido';


    protected $fillable = [
        'codnegocio',
        'codsauruspdv',
        'id',
        'idfaturapag',
        'idpedido',
        'modpagamento',
        'parcelas',
        'status',
        'valor',
        'valorjuros',
        'valorparcela',
        'valortotal'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codnegocio' => 'integer',
        'codsauruspdv' => 'integer',
        'codsauruspedido' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'modpagamento' => 'integer',
        'parcelas' => 'integer',
        'status' => 'integer',
        'valor' => 'float',
        'valorjuros' => 'float',
        'valorparcela' => 'float',
        'valortotal' => 'float'
    ];


    // Chaves Estrangeiras
    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function SaurusPdv()
    {
        return $this->belongsTo(SaurusPdv::class, 'codsauruspdv', 'codsauruspdv');
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
    public function NegocioFormaPagamentoS()
    {
        return $this->hasMany(NegocioFormaPagamento::class, 'codsauruspedido', 'codsauruspedido');
    }

    public function SaurusPagamentoS()
    {
        return $this->hasMany(SaurusPagamento::class, 'codsauruspedido', 'codsauruspedido');
    }

}