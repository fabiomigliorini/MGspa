<?php
/**
 * Created by php artisan gerador:model.
 * Date: 19/Nov/2022 12:10:57
 */

namespace Mg\Mercos;

use Mg\MgModel;
use Mg\Mercos\MercosPedidoItem;
use Mg\Mercoscliente\Mercoscliente;
use Mg\Negocio\Negocio;
use Mg\Usuario\Usuario;

class MercosPedido extends MgModel
{
    protected $table = 'tblmercospedido';
    protected $primaryKey = 'codmercospedido';


    protected $fillable = [
        'codmercoscliente',
        'codnegocio',
        'condicaopagamento',
        'enderecoentrega',
        'faturamentoid',
        'numero',
        'pedidoid',
        'ultimaalteracaomercos'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'ultimaalteracaomercos'
    ];

    protected $casts = [
        'codmercoscliente' => 'integer',
        'codmercospedido' => 'integer',
        'codnegocio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'faturamentoid' => 'integer',
        'numero' => 'integer',
        'pedidoid' => 'integer'
    ];


    // Chaves Estrangeiras
    public function MercosCliente()
    {
        return $this->belongsTo(Mercoscliente::class, 'codmercoscliente', 'codmercoscliente');
    }

    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
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
    public function MercosPedidoItemS()
    {
        return $this->hasMany(MercosPedidoItem::class, 'codmercospedido', 'codmercospedido');
    }

}