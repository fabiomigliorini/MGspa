<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/May/2021 15:03:14
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Produto\Produto;
use Mg\Produto\ProdutoEmbalagem;
use Mg\Produto\ProdutoVariacao;
use Mg\Imagem\Imagem;
use Mg\Usuario\Usuario;

class ProdutoImagem extends MgModel
{
    protected $table = 'tblprodutoimagem';
    protected $primaryKey = 'codprodutoimagem';


    protected $fillable = [
        'codimagem',
        'codproduto',
        'ordem'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codimagem' => 'integer',
        'codproduto' => 'integer',
        'codprodutoimagem' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'ordem' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Imagem()
    {
        return $this->belongsTo(Imagem::class, 'codimagem', 'codimagem');
    }

    public function Produto()
    {
        return $this->belongsTo(Produto::class, 'codproduto', 'codproduto');
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
    public function ProdutoS()
    {
        return $this->hasMany(Produto::class, 'codprodutoimagem', 'codprodutoimagem');
    }

    public function ProdutoEmbalagemS()
    {
        return $this->hasMany(ProdutoEmbalagem::class, 'codprodutoimagem', 'codprodutoimagem');
    }

    public function ProdutoVariacaoS()
    {
        return $this->hasMany(ProdutoVariacao::class, 'codprodutoimagem', 'codprodutoimagem');
    }

}