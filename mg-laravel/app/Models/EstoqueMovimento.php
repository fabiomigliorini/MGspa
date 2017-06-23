<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codestoquemovimento                NOT NULL DEFAULT nextval('tblestoquemovimento_codestoquemovimento_seq'::regclass)
 * @property  bigint                         $codestoquemovimentotipo            NOT NULL
 * @property  numeric(14,3)                  $entradaquantidade                  
 * @property  numeric(14,2)                  $entradavalor                       
 * @property  numeric(14,3)                  $saidaquantidade                    
 * @property  numeric(14,2)                  $saidavalor                         
 * @property  bigint                         $codnegocioprodutobarra             
 * @property  bigint                         $codnotafiscalprodutobarra          
 * @property  bigint                         $codestoquemes                      NOT NULL
 * @property  boolean                        $manual                             NOT NULL DEFAULT false
 * @property  timestamp                      $data                               NOT NULL
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuariocriacao                  
 * @property  bigint                         $codestoquemovimentoorigem          
 * @property  varchar(200)                   $observacoes                        
 * @property  bigint                         $codestoquesaldoconferencia         
 *
 * Chaves Estrangeiras
 * @property  EstoqueMes                     $EstoqueMes
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 * @property  EstoqueMovimento               $EstoqueMovimentoOrigem
 * @property  EstoqueSaldoConferencia        $EstoqueSaldoConferencia
 * @property  EstoqueMovimentoTipo           $EstoqueMovimentoTipo
 * @property  NegocioProdutoBarra            $NegocioProdutoBarra
 * @property  NotaFiscalProdutoBarra         $NotaFiscalProdutoBarra
 *
 * Tabelas Filhas
 * @property  EstoqueMovimento[]             $EstoqueMovimentoDestinoS
 */

class EstoqueMovimento extends MGModel
{
    protected $table = 'tblestoquemovimento';
    protected $primaryKey = 'codestoquemovimento';
    protected $fillable = [
        'codestoquemovimentotipo',
        'entradaquantidade',
        'entradavalor',
        'saidaquantidade',
        'saidavalor',
        'codnegocioprodutobarra',
        'codnotafiscalprodutobarra',
        'codestoquemes',
        'manual',
        'data',
        'codestoquemovimentoorigem',
        'observacoes',
        'codestoquesaldoconferencia',
        'codestoquelocal',
        'codprodutovariacao',
        'codproduto',
        'fiscal',
    ];
    protected $dates = [
        'data',
        'alteracao',
        'criacao',
    ];


    // Chaves Estrangeiras
    public function EstoqueMes()
    {
        return $this->belongsTo(EstoqueMes::class, 'codestoquemes', 'codestoquemes');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function EstoqueMovimentoOrigem()
    {
        return $this->belongsTo(EstoqueMovimento::class, 'codestoquemovimentoorigem', 'codestoquemovimento');
    }

    public function EstoqueSaldoConferencia()
    {
        return $this->belongsTo(EstoqueSaldoConferencia::class, 'codestoquesaldoconferencia', 'codestoquesaldoconferencia');
    }

    public function EstoqueMovimentoTipo()
    {
        return $this->belongsTo(EstoqueMovimentoTipo::class, 'codestoquemovimentotipo', 'codestoquemovimentotipo');
    }

    public function NegocioProdutoBarra()
    {
        return $this->belongsTo(NegocioProdutoBarra::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }

    public function NotaFiscalProdutoBarra()
    {
        return $this->belongsTo(NotaFiscalProdutoBarra::class, 'codnotafiscalprodutobarra', 'codnotafiscalprodutobarra');
    }


    // Tabelas Filhas
    public function EstoqueMovimentoDestinoS()
    {
        return $this->hasMany(EstoqueMovimento::class, 'codestoquemovimentoorigem', 'codestoquemovimento');
    }


}
