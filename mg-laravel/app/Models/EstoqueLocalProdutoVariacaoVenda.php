<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codestoquelocalprodutovariacaovenda NOT NULL DEFAULT nextval('tblestoquelocalprodutovariaca_codestoquelocalprodutovariaca_seq'::regclass)
 * @property  bigint                         $codestoquelocalprodutovariacao     NOT NULL
 * @property  date                           $mes                                NOT NULL
 * @property  numeric(14,2)                  $quantidade                         
 * @property  numeric(14,2)                  $valor                              
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuariocriacao                  
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 *
 * Chaves Estrangeiras
 * @property  EstoqueLocalProdutoVariacao    $EstoqueLocalProdutoVariacao
 * @property  Usuario                        $UsuarioCriacao
 * @property  Usuario                        $UsuarioAlteracao
 *
 * Tabelas Filhas
 */

class EstoqueLocalProdutoVariacaoVenda extends MGModel
{
    protected $table = 'tblestoquelocalprodutovariacaovenda';
    protected $primaryKey = 'codestoquelocalprodutovariacaovenda';
    protected $fillable = [
        'codestoquelocalprodutovariacao',
        'mes',
        'quantidade',
        'valor',
    ];
    protected $dates = [
        'mes',
        'criacao',
        'alteracao',
    ];

    // Chaves Estrangeiras
    public function EstoqueLocalProdutoVariacao()
    {
        return $this->belongsTo(EstoqueLocalProdutoVariacao::class, 'codestoquelocalprodutovariacao', 'codestoquelocalprodutovariacao');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    // Tabelas Filhas

}
