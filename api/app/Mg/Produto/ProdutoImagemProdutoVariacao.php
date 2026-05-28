<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:40:11
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Produto\ProdutoImagem;
use Mg\Produto\ProdutoVariacao;
use Mg\Usuario\Usuario;

class ProdutoImagemProdutoVariacao extends MgModel
{
    protected $table = 'tblprodutoimagemprodutovariacao';
    protected $primaryKey = 'codprodutoimagemprodutovariacao';


    protected $fillable = [
        'codprodutoimagem',
        'codprodutovariacao'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codprodutoimagem' => 'integer',
        'codprodutoimagemprodutovariacao' => 'integer',
        'codprodutovariacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function ProdutoImagem()
    {
        return $this->belongsTo(ProdutoImagem::class, 'codprodutoimagem', 'codprodutoimagem');
    }

    public function ProdutoVariacao()
    {
        return $this->belongsTo(ProdutoVariacao::class, 'codprodutovariacao', 'codprodutovariacao');
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
