<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codestoquelocalprodutovariacao     NOT NULL DEFAULT nextval('tblestoquelocalproduto_codestoquelocalproduto_seq'::regclass)
 * @property  bigint                         $codestoquelocal                    NOT NULL
 * @property  bigint                         $corredor                           
 * @property  bigint                         $prateleira                         
 * @property  bigint                         $coluna                             
 * @property  bigint                         $bloco                              
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuariocriacao                  
 * @property  bigint                         $estoqueminimo                      
 * @property  bigint                         $estoquemaximo                      
 * @property  bigint                         $codprodutovariacao                 NOT NULL
 * @property  numeric(14,3)                  $vendabimestrequantidade            
 * @property  numeric(14,2)                  $vendabimestrevalor                 
 * @property  numeric(14,3)                  $vendasemestrequantidade            
 * @property  numeric(14,2)                  $vendasemestrevalor                 
 * @property  numeric(14,3)                  $vendaanoquantidade                 
 * @property  numeric(14,2)                  $vendaanovalor                      
 * @property  timestamp                      $vendaultimocalculo                 
 * @property  date                           $vencimento                         
 * @property  float8                         $vendadiaquantidadeprevisao         
 *
 * Chaves Estrangeiras
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 * @property  EstoqueLocal                   $EstoqueLocal
 * @property  ProdutoVariacao                $ProdutoVariacao
 *
 * Tabelas Filhas
 * @property  EstoqueLocalProdutoVariacaoVenda[] $EstoqueLocalProdutoVariacaoVendaS
 * @property  EstoqueSaldo[]                 $EstoqueSaldoS
 */

class EstoqueLocalProdutoVariacao extends MGModel
{
    protected $table = 'tblestoquelocalprodutovariacao';
    protected $primaryKey = 'codestoquelocalprodutovariacao';
    protected $fillable = [
        'codestoquelocal',
        'corredor',
        'prateleira',
        'coluna',
        'bloco',
        'estoqueminimo',
        'estoquemaximo',
        'codprodutovariacao',
        'vendabimestrequantidade',
        'vendabimestrevalor',
        'vendasemestrequantidade',
        'vendasemestrevalor',
        'vendaanoquantidade',
        'vendaanovalor',
        'vendaultimocalculo',
        'vencimento',
        'vendadiaquantidadeprevisao',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
        'vendaultimocalculo',
        'vencimento',
    ];

    // Chaves Estrangeiras
    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function EstoqueLocal()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocal', 'codestoquelocal');
    }

    public function ProdutoVariacao()
    {
        return $this->belongsTo(ProdutoVariacao::class, 'codprodutovariacao', 'codprodutovariacao');
    }

    // Tabelas Filhas
    public function EstoqueLocalProdutoVariacaoVendaS()
    {
        return $this->hasMany(EstoqueLocalProdutoVariacaoVenda::class, 'codestoquelocalprodutovariacao', 'codestoquelocalprodutovariacao');
    }

    public function EstoqueSaldoS()
    {
        return $this->hasMany(EstoqueSaldo::class, 'codestoquelocalprodutovariacao', 'codestoquelocalprodutovariacao');
    }


}
