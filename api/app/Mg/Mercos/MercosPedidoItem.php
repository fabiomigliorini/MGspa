<?php
/**
 * Created by php artisan gerador:model.
 * Date: 11/May/2024 16:00:30
 */

namespace Mg\Mercos;

use Mg\MgModel;
use Mg\Mercos\MercosPedido;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\Usuario\Usuario;

class MercosPedidoItem extends MgModel
{
    protected $table = 'tblmercospedidoitem';
    protected $primaryKey = 'codmercospedidoitem';


    protected $fillable = [
        'codmercospedido',
        'codnegocioprodutobarra',
        'itemid'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codmercospedido' => 'integer',
        'codmercospedidoitem' => 'integer',
        'codnegocioprodutobarra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'itemid' => 'integer'
    ];


    // Chaves Estrangeiras
    public function MercosPedido()
    {
        return $this->belongsTo(MercosPedido::class, 'codmercospedido', 'codmercospedido');
    }

    public function NegocioProdutoBarra()
    {
        return $this->belongsTo(NegocioProdutoBarra::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

}