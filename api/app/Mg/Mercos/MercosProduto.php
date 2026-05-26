<?php
/**
 * Created by php artisan gerador:model.
 * Date: 11/May/2024 15:59:50
 */

namespace Mg\Mercos;

use Mg\MgModel;
use Mg\Mercos\MercosProdutoImagem;
use Mg\Produto\Produto;
use Mg\Produto\ProdutoEmbalagem;
use Mg\Produto\ProdutoVariacao;
use Mg\Usuario\Usuario;

class MercosProduto extends MgModel
{
    protected $table = 'tblmercosproduto';
    protected $primaryKey = 'codmercosproduto';


    protected $fillable = [
        'codproduto',
        'codprodutoembalagem',
        'codprodutovariacao',
        'inativo',
        'preco',
        'precoatualizado',
        'produtoid',
        'saldoquantidade',
        'saldoquantidadeatualizado'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
        'precoatualizado',
        'saldoquantidadeatualizado'
    ];

    protected $casts = [
        'codmercosproduto' => 'integer',
        'codproduto' => 'integer',
        'codprodutoembalagem' => 'integer',
        'codprodutovariacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'preco' => 'float',
        'produtoid' => 'integer',
        'saldoquantidade' => 'float'
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


    // Tabelas Filhas
    public function MercosProdutoImagemS()
    {
        return $this->hasMany(MercosProdutoImagem::class, 'codmercosproduto', 'codmercosproduto');
    }

}