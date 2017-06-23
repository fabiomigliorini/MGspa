<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codprodutobarra                    NOT NULL DEFAULT nextval('tblprodutobarra_codprodutobarra_seq'::regclass)
 * @property  bigint                         $codproduto                         NOT NULL
 * @property  varchar(100)                   $variacao                           
 * @property  varchar(50)                    $barras                             NOT NULL
 * @property  varchar(50)                    $referencia                         
 * @property  bigint                         $codmarca                           
 * @property  bigint                         $codprodutoembalagem                
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuariocriacao                  
 * @property  bigint                         $codprodutovariacao                 NOT NULL
 *
 * Chaves Estrangeiras
 * @property  ProdutoVariacao                $ProdutoVariacao
 * @property  Marca                          $Marca
 * @property  Produto                        $Produto
 * @property  ProdutoEmbalagem               $ProdutoEmbalagem
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  ValeCompraModeloProdutoBarra[] $ValeCompraModeloProdutoBarraS
 * @property  ValeCompraProdutoBarra[]       $ValeCompraProdutoBarraS
 * @property  CupomFiscalProdutoBarra[]      $CupomFiscalProdutoBarraS
 * @property  NegocioProdutoBarra[]          $NegocioProdutoBarraS
 * @property  NfeTerceiroItem[]              $NfeTerceiroItemS
 * @property  NotaFiscalProdutoBarra[]       $NotaFiscalProdutoBarraS
 */

class ProdutoBarra extends MGModel
{
    protected $table = 'tblprodutobarra';
    protected $primaryKey = 'codprodutobarra';
    protected $fillable = [
        'codproduto',
        'variacao',
        'barras',
        'referencia',
        'codmarca',
        'codprodutoembalagem',
        'codprodutovariacao',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
    ];

    // Atributo descricao
    public function getDescricaoAttribute()
    {
        $descr = "{$this->Produto->produto} {$this->ProdutoVariacao->variacao}";
        if ($this->codprodutoembalagem) {
            $quant = formataNumero($this->ProdutoEmbalagem->quantidade, 0);
            $descr = "{$descr} C/{$quant}";
        }
        return trim($descr);
    }
    
    /*
    // Atributo referencia
    public function getReferenciaAttribute()
    {
        if (!empty($this->referencia)) {
            return $this->referencia;
        }
        if (!empty($this->ProdutoVariacao->referencia)) {
            return $this->ProdutoVariacao->referencia;
        } 
        
        return $this->Produto->referencia;
    }

    // Atributo preco
    public function getPrecoAttribute()
    {
        if (!empty($this->codprodutoembalagem)) {
            if (empty($this->ProdutoEmbalagem->preco)) {
                return $this->ProdutoEmbalagem->quantidade * $this->produto->preco;
            }
            return (float) $this->ProdutoEmbalagem->preco;
        }
        return (float) $this->Produto->preco;
    }    
    */
    
    // Chaves Estrangeiras
    public function ProdutoVariacao()
    {
        return $this->belongsTo(ProdutoVariacao::class, 'codprodutovariacao', 'codprodutovariacao');
    }

//    public function Marca()
//    {
//        return $this->belongsTo(Marca::class, 'codmarca', 'codmarca');
//    }

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


    // Tabelas Filhas
    public function ValeCompraModeloProdutoBarraS()
    {
        return $this->hasMany(ValeCompraModeloProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function ValeCompraProdutoBarraS()
    {
        return $this->hasMany(ValeCompraProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function CupomFiscalProdutoBarraS()
    {
        return $this->hasMany(CupomFiscalProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function NegocioProdutoBarraS()
    {
        return $this->hasMany(NegocioProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function NfeTerceiroItemS()
    {
        return $this->hasMany(NfeTerceiroItem::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function NotaFiscalProdutoBarraS()
    {
        return $this->hasMany(NotaFiscalProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }
    
    public function UnidadeMedida()
    {
        if (!empty($this->codprodutoembalagem)) {
            return $this->ProdutoEmbalagem->UnidadeMedida();
        } 
        return $this->Produto->UnidadeMedida();
    }
    
    public function Marca()
    {
        if (!empty($this->ProdutoVariacao->codmarca)) {
            return $this->ProdutoVariacao->Marca();
        } 
        return $this->Produto->Marca();
    }
    
}
