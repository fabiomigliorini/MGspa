<?php

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Marca\Marca;
use Mg\NaturezaOperacao\Ncm;
use Mg\NaturezaOperacao\Cest;

/**
 * Campos
 * @property  bigint                         $codproduto                         NOT NULL DEFAULT nextval('tblproduto_codproduto_seq'::regclass)
 * @property  varchar(100)                   $produto                            NOT NULL
 * @property  varchar(50)                    $referencia
 * @property  bigint                         $codunidademedida                   NOT NULL
 * @property  bigint                         $codsubgrupoproduto                 NOT NULL
 * @property  bigint                         $codmarca                           NOT NULL
 * @property  numeric(14,2)                  $preco
 * @property  boolean                        $importado                          NOT NULL DEFAULT false
 * @property  bigint                         $codtributacao                      NOT NULL
 * @property  date                           $inativo
 * @property  bigint                         $codtipoproduto                     NOT NULL
 * @property  boolean                        $site                               NOT NULL DEFAULT false
 * @property  text                           $descricaosite
 * @property  timestamp                      $alteracao
 * @property  bigint                         $codusuarioalteracao
 * @property  timestamp                      $criacao
 * @property  bigint                         $codusuariocriacao
 * @property  bigint                         $codncm
 * @property  bigint                         $codcest
 * @property  varchar(255)                   $observacoes
 * @property  bigint                         $codopencart
 * @property  bigint                         $codopencartvariacao
 * @property  numeric(7,4)                   $peso
 * @property  numeric(8,2)                   $altura
 * @property  numeric(8,2)                   $largura
 * @property  numeric(8,2)                   $profundidade
 * @property  boolean                        $vendesite                          NOT NULL DEFAULT false
 * @property  varchar(200)                   $metakeywordsite
 * @property  text                           $metadescriptionsite
 * @property  bigint                         $codprodutoimagem
 *
 * Chaves Estrangeiras
 * @property  Cest                           $Cest
 * @property  Ncm                            $Ncm
 * @property  Marca                          $Marca
 * @property  SubGrupoProduto                $SubGrupoProduto
 * @property  TipoProduto                    $TipoProduto
 * @property  Tributacao                     $Tributacao
 * @property  UnidadeMedida                  $UnidadeMedida
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 * @property  ProdutoImagem                  $ProdutoImagem
 *
 * Tabelas Filhas
 * @property  PranchetaProduto[]             $PranchetaProdutoS
 * @property  ProdutoImagem[]                $ProdutoImagemS
 * @property  ProdutoVariacao[]              $ProdutoVariacaoS
 * @property  ProdutoBarra[]                 $ProdutoBarraS
 * @property  ProdutoEmbalagem[]             $ProdutoEmbalagemS
 * @property  ProdutoHistoricoPreco[]        $ProdutoHistoricoPrecoS
 *
 * Relacionamentos N x N
 * @property  Imagem[]                       $ImagemS
 */
class Produto extends MgModel
{
    protected $table = 'tblproduto';
    protected $primaryKey = 'codproduto';
    protected $fillable = [
        'produto',
        'referencia',
        'codunidademedida',
        'codsubgrupoproduto',
        'codmarca',
        'preco',
        'importado',
        'codtributacao',
        'codtipoproduto',
        'site',
        'descricaosite',
        'codncm',
        'codcest',
        'observacoes',
        'codopencart',
        'codopencartvariacao',
        'peso',
        'altura',
        'largura',
        'profundidade',
        'vendesite',
        'metakeywordsite',
        'metadescriptionsite',
        'codprodutoimagem',
    ];
    protected $dates = [
        'inativo',
        'alteracao',
        'criacao',
    ];


    // Chaves Estrangeiras
    public function Cest()
    {
        return $this->belongsTo(Cest::class, 'codcest', 'codcest');
    }

    public function Ncm()
    {
        return $this->belongsTo(Ncm::class, 'codncm', 'codncm');
    }

    public function Marca()
    {
        return $this->belongsTo(Marca::class, 'codmarca', 'codmarca');
    }

    public function SubGrupoProduto()
    {
        return $this->belongsTo(SubGrupoProduto::class, 'codsubgrupoproduto', 'codsubgrupoproduto');
    }

    public function TipoProduto()
    {
        return $this->belongsTo(TipoProduto::class, 'codtipoproduto', 'codtipoproduto');
    }

    public function Tributacao()
    {
        return $this->belongsTo(Tributacao::class, 'codtributacao', 'codtributacao');
    }

    public function UnidadeMedida()
    {
        return $this->belongsTo(UnidadeMedida::class, 'codunidademedida', 'codunidademedida');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function ProdutoImagem()
    {
        return $this->belongsTo(ProdutoImagem::class, 'codprodutoimagem', 'codprodutoimagem');
    }


    // Tabelas Filhas
    public function PranchetaProdutoS()
    {
        return $this->hasMany(PranchetaProduto::class, 'codproduto', 'codproduto');
    }

    public function ProdutoImagemS()
    {
        return $this->hasMany(ProdutoImagem::class, 'codproduto', 'codproduto');
    }

    public function ProdutoVariacaoS()
    {
        return $this->hasMany(ProdutoVariacao::class, 'codproduto', 'codproduto');
    }

    public function ProdutoBarraS()
    {
        return $this->hasMany(ProdutoBarra::class, 'codproduto', 'codproduto');
    }

    public function ProdutoEmbalagemS()
    {
        return $this->hasMany(ProdutoEmbalagem::class, 'codproduto', 'codproduto');
    }

    public function ProdutoHistoricoPrecoS()
    {
        return $this->hasMany(ProdutoHistoricoPreco::class, 'codproduto', 'codproduto');
    }


    // Relacionamento N x N
    public function ImagemS()
    {
        return $this->belongsToMany(Imagem::class, 'tblprodutoimagem', 'codproduto', 'codimagem');
    }


}
