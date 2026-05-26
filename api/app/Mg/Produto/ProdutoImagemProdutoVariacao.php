<?php
/**
 * Created by php artisan gerador:model.
 * Date: 11/Jun/2025 10:01:04
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codprodutoimagem' => 'integer',
        'codprodutoimagemprodutovariacao' => 'integer',
        'codprodutovariacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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