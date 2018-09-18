<?php

namespace Mg\Produto;

/**
 * Campos
 * @property  bigint                         $codprodutovariacao                 NOT NULL DEFAULT nextval('tblprodutovariacao_codprodutovariacao_seq'::regclass)
 * @property  bigint                         $codproduto                         NOT NULL
 * @property  varchar(100)                   $variacao
 * @property  varchar(50)                    $referencia
 * @property  bigint                         $codmarca
 * @property  timestamp                      $alteracao
 * @property  bigint                         $codusuarioalteracao
 * @property  timestamp                      $criacao
 * @property  bigint                         $codusuariocriacao
 * @property  bigint                         $codopencart
 * @property  date                           $dataultimacompra
 * @property  numeric(14,6)                  $custoultimacompra
 * @property  numeric(14,3)                  $quantidadeultimacompra
 * @property  timestamp                      $inativo
 * @property  bigint                         $codprodutoimagem
 * @property  date                           $vendainicio
 *
 * Chaves Estrangeiras
 * @property  Marca                          $Marca
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 * @property  Produto                        $Produto
 * @property  ProdutoImagem                  $ProdutoImagem
 *
 * Tabelas Filhas
 * @property  EstoqueLocalProdutoVariacao[]  $EstoqueLocalProdutoVariacaoS
 * @property  ProdutoBarra[]                 $ProdutoBarraS
 */
 use Mg\MgModel;
 use Mg\Estoque\EstoqueLocalProdutoVariacao;

class ProdutoVariacao extends MgModel
{
    protected $table = 'tblprodutovariacao';
    protected $primaryKey = 'codprodutovariacao';
    protected $fillable = [
        'codproduto',
        'variacao',
        'referencia',
        'codmarca',
        'codopencart',
        'dataultimacompra',
        'custoultimacompra',
        'quantidadeultimacompra',
        'codprodutoimagem',
        'vendainicio',
        'estoqueminimo',
        'estoquemaximo',
        'lotecompra',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
        'dataultimacompra',
        'inativo',
        'vendainicio',
    ];


    // Chaves Estrangeiras
    public function Marca()
    {
        return $this->belongsTo(Marca::class, 'codmarca', 'codmarca');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function Produto()
    {
        return $this->belongsTo(Produto::class, 'codproduto', 'codproduto');
    }

    public function ProdutoImagem()
    {
        return $this->belongsTo(ProdutoImagem::class, 'codprodutoimagem', 'codprodutoimagem');
    }


    // Tabelas Filhas
    public function EstoqueLocalProdutoVariacaoS()
    {
        return $this->hasMany(EstoqueLocalProdutoVariacao::class, 'codprodutovariacao', 'codprodutovariacao');
    }

    public function ProdutoBarraS()
    {
        return $this->hasMany(ProdutoBarra::class, 'codprodutovariacao', 'codprodutovariacao');
    }


}
