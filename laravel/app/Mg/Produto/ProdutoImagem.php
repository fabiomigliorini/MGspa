<?php

namespace Mg\Produto;

/**
 * Campos
 * @property  bigint                         $codprodutoimagem                   NOT NULL DEFAULT nextval('tblprodutoimagem_codprodutoimagem_seq'::regclass)
 * @property  bigint                         $codproduto                         NOT NULL
 * @property  bigint                         $codimagem                          NOT NULL
 * @property  timestamp                      $criacao
 * @property  bigint                         $codusuariocriacao
 * @property  timestamp                      $alteracao
 * @property  bigint                         $codusuarioalteracao
 * @property  integer                        $ordem                              NOT NULL DEFAULT 1
 *
 * Chaves Estrangeiras
 * @property  Produto                        $Produto
 * @property  Imagem                         $Imagem
 *
 * Tabelas Filhas
 * @property  ProdutoEmbalagem[]             $ProdutoEmbalagemS
 * @property  Produto[]                      $ProdutoS
 * @property  ProdutoVariacao[]              $ProdutoVariacaoS
 */
 use Mg\MgModel;
 use Mg\Imagem\Imagem;

class ProdutoImagem extends MgModel
{
    protected $table = 'tblprodutoimagem';
    protected $primaryKey = 'codprodutoimagem';
    protected $fillable = [
        'codproduto',
        'codimagem',
        'ordem',
    ];
    protected $dates = [
        'criacao',
        'alteracao',
    ];


    // Chaves Estrangeiras
    public function Produto()
    {
        return $this->belongsTo(Produto::class, 'codproduto', 'codproduto');
    }

    public function Imagem()
    {
        return $this->belongsTo(Imagem::class, 'codimagem', 'codimagem');
    }


    // Tabelas Filhas
    public function ProdutoEmbalagemS()
    {
        return $this->hasMany(ProdutoEmbalagem::class, 'codprodutoimagem', 'codprodutoimagem');
    }

    public function ProdutoS()
    {
        return $this->hasMany(Produto::class, 'codprodutoimagem', 'codprodutoimagem');
    }

    public function ProdutoVariacaoS()
    {
        return $this->hasMany(ProdutoVariacao::class, 'codprodutoimagem', 'codprodutoimagem');
    }

}
