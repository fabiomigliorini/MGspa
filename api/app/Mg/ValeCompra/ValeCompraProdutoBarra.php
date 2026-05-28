<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:23:39
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codprodutobarra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codvalecompra' => 'integer',
        'codvalecompraprodutobarra' => 'integer',
        'criacao' => 'datetime',
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
