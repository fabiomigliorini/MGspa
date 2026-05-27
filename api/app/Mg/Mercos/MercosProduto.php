<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:37:51
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codmercosproduto' => 'integer',
        'codproduto' => 'integer',
        'codprodutoembalagem' => 'integer',
        'codprodutovariacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'preco' => 'float',
        'precoatualizado' => 'datetime',
        'produtoid' => 'integer',
        'saldoquantidade' => 'float',
        'saldoquantidadeatualizado' => 'datetime'
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
