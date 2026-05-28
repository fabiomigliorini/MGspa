<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:33:12
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Produto\Produto;
use Mg\Produto\ProdutoEmbalagem;
use Mg\Usuario\Usuario;

class ProdutoHistoricoPreco extends MgModel
{
    protected $table = 'tblprodutohistoricopreco';
    protected $primaryKey = 'codprodutohistoricopreco';


    protected $fillable = [
        'codproduto',
        'codprodutoembalagem',
        'precoantigo',
        'preconovo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codproduto' => 'integer',
        'codprodutoembalagem' => 'integer',
        'codprodutohistoricopreco' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'precoantigo' => 'float',
        'preconovo' => 'float'
    ];


    // Chaves Estrangeiras
    public function Produto()
    {
        return $this->belongsTo(Produto::class, 'codproduto', 'codproduto');
    }

    public function ProdutoEmbalagem()
    {
        return $this->belongsTo(ProdutoEmbalagem::class, 'codprodutoembalagem', 'codprodutoembalagem');
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
