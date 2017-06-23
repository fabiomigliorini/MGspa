<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codprodutoembalagem                NOT NULL DEFAULT nextval('tblprodutoembalagem_codprodutoembalagem_seq'::regclass)
 * @property  bigint                         $codproduto                         
 * @property  bigint                         $codunidademedida                   
 * @property  numeric(17,5)                  $quantidade                         
 * @property  numeric(14,2)                  $preco                              
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuariocriacao                  
 * @property  numeric(7,4)                   $peso                               
 * @property  numeric(8,2)                   $altura                             
 * @property  numeric(8,2)                   $largura                            
 * @property  numeric(8,2)                   $profundidade                       
 * @property  boolean                        $vendesite                          NOT NULL DEFAULT false
 * @property  text                           $descricaosite                      
 * @property  bigint                         $codopencart                        
 * @property  bigint                         $codprodutoimagem                   
 *
 * Chaves Estrangeiras
 * @property  Produto                        $Produto
 * @property  UnidadeMedida                  $UnidadeMedida
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 * @property  ProdutoImagem                  $ProdutoImagem
 *
 * Tabelas Filhas
 * @property  ProdutoBarra[]                 $ProdutoBarraS
 * @property  ProdutoHistoricoPreco[]        $ProdutoHistoricoPrecoS
 */

class ProdutoEmbalagem extends MGModel
{
    protected $table = 'tblprodutoembalagem';
    protected $primaryKey = 'codprodutoembalagem';
    protected $fillable = [
        'codproduto',
        'codunidademedida',
        'quantidade',
        'preco',
        'peso',
        'altura',
        'largura',
        'profundidade',
        'vendesite',
        'descricaosite',
        'codopencart',
        'codprodutoimagem',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
    ];

    public function getDescricaoAttribute()
    {
        if (floor($this->quantidade) == $this->quantidade)
            $digitos = 0;
        else
            $digitos = 5;

        return $this->UnidadeMedida->sigla . ' C/' . formataNumero($this->quantidade, $digitos);
    }
    
    public function getPrecoCalculadoAttribute()
    {
        if ($this->Produto)
            $preco_calculado = ($this->preco) ? $this->preco : $this->Produto->preco * $this->quantidade;
        
        return $preco_calculado;
    }
    
    // Chaves Estrangeiras
    public function Produto()
    {
        return $this->belongsTo(Produto::class, 'codproduto', 'codproduto');
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
    public function ProdutoBarraS()
    {
        return $this->hasMany(ProdutoBarra::class, 'codprodutoembalagem', 'codprodutoembalagem');
    }

    public function ProdutoHistoricoPrecoS()
    {
        return $this->hasMany(ProdutoHistoricoPreco::class, 'codprodutoembalagem', 'codprodutoembalagem');
    }


}
