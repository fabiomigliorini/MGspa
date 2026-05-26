<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/May/2021 15:27:04
 */

namespace Mg\ValeCompra;

use Mg\MgModel;
use Mg\Produto\ProdutoBarra;
use Mg\Usuario\Usuario;
use Mg\ValeCompra\ValeCompra;

class ValeCompraProdutoBarra extends MgModel
{
    protected $table = 'tblvalecompraprodutobarra';
    protected $primaryKey = 'codvalecompraprodutobarra';


    protected $fillable = [
        'codprodutobarra',
        'codvalecompra',
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
        'codvalecompra' => 'integer',
        'codvalecompraprodutobarra' => 'integer',
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

    public function ValeCompra()
    {
        return $this->belongsTo(ValeCompra::class, 'codvalecompra', 'codvalecompra');
    }

}