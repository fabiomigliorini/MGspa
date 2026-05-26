<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/May/2021 15:26:47
 */

namespace Mg\ValeCompra;

use Mg\MgModel;
use Mg\Produto\ProdutoBarra;
use Mg\Usuario\Usuario;
use Mg\ValeCompra\ValeCompraModelo;

class ValeCompraModeloProdutoBarra extends MgModel
{
    protected $table = 'tblvalecompramodeloprodutobarra';
    protected $primaryKey = 'codvalecompramodeloprodutobarra';


    protected $fillable = [
        'codprodutobarra',
        'codvalecompramodelo',
        'preco',
        'quantidade',
        'total'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codprodutobarra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codvalecompramodelo' => 'integer',
        'codvalecompramodeloprodutobarra' => 'integer',
        'preco' => 'float',
        'quantidade' => 'float',
        'total' => 'float'
    ];


    // Chaves Estrangeiras
    public function ProdutoBarra()
    {
        return $this->belongsTo(ProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function ValeCompraModelo()
    {
        return $this->belongsTo(ValeCompraModelo::class, 'codvalecompramodelo', 'codvalecompramodelo');
    }

}