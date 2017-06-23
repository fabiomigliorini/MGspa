<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codnotafiscalprodutobarra          NOT NULL DEFAULT nextval('tblnotafiscalprodutobarra_codnotafiscalprodutobarra_seq'::regclass)
 * @property  bigint                         $codnotafiscal                      NOT NULL
 * @property  bigint                         $codprodutobarra                    NOT NULL
 * @property  bigint                         $codcfop                            NOT NULL
 * @property  varchar(100)                   $descricaoalternativa               
 * @property  numeric(14,3)                  $quantidade                         NOT NULL
 * @property  numeric(14,3)                  $valorunitario                      NOT NULL
 * @property  numeric(14,2)                  $valortotal                         NOT NULL
 * @property  numeric(14,2)                  $icmsbase                           
 * @property  numeric(14,2)                  $icmspercentual                     
 * @property  numeric(14,2)                  $icmsvalor                          
 * @property  numeric(14,2)                  $ipibase                            
 * @property  numeric(14,2)                  $ipipercentual                      
 * @property  numeric(14,2)                  $ipivalor                           
 * @property  numeric(14,2)                  $icmsstbase                         
 * @property  numeric(14,2)                  $icmsstpercentual                   
 * @property  numeric(14,2)                  $icmsstvalor                        
 * @property  varchar(4)                     $csosn                              
 * @property  bigint                         $codnegocioprodutobarra             
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuariocriacao                  
 * @property  numeric(3,0)                   $icmscst                            
 * @property  numeric(3,0)                   $ipicst                             
 * @property  numeric(3,0)                   $piscst                             
 * @property  numeric(14,2)                  $pisbase                            
 * @property  numeric(4,2)                   $pispercentual                      
 * @property  numeric(14,2)                  $pisvalor                           
 * @property  numeric(3,0)                   $cofinscst                          
 * @property  numeric(14,2)                  $cofinsbase                         
 * @property  numeric(14,2)                  $cofinsvalor                        
 * @property  numeric(14,2)                  $csllbase                           
 * @property  numeric(4,2)                   $csllpercentual                     
 * @property  numeric(14,2)                  $csllvalor                          
 * @property  numeric(14,2)                  $irpjbase                           
 * @property  numeric(4,2)                   $irpjpercentual                     
 * @property  numeric(14,2)                  $irpjvalor                          
 * @property  numeric(4,2)                   $cofinspercentual                   
 * @property  bigint                         $codnotafiscalprodutobarraorigem    
 *
 * Chaves Estrangeiras
 * @property  NotaFiscalProdutoBarra         $NotaFiscalProdutoBarra
 * @property  Cfop                           $Cfop
 * @property  NegocioProdutoBarra            $NegocioProdutoBarra
 * @property  NotaFiscal                     $NotaFiscal
 * @property  ProdutoBarra                   $ProdutoBarra
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  NotaFiscalProdutoBarra[]       $NotaFiscalProdutoBarraS
 * @property  EstoqueMovimento[]             $EstoqueMovimentoS
 */

class NotaFiscalProdutoBarra extends MGModel
{
    protected $table = 'tblnotafiscalprodutobarra';
    protected $primaryKey = 'codnotafiscalprodutobarra';
    protected $fillable = [
          'codnotafiscal',
         'codprodutobarra',
         'codcfop',
         'descricaoalternativa',
         'quantidade',
         'valorunitario',
         'valortotal',
         'icmsbase',
         'icmspercentual',
         'icmsvalor',
         'ipibase',
         'ipipercentual',
         'ipivalor',
         'icmsstbase',
         'icmsstpercentual',
         'icmsstvalor',
         'csosn',
         'codnegocioprodutobarra',
             'icmscst',
         'ipicst',
         'piscst',
         'pisbase',
         'pispercentual',
         'pisvalor',
         'cofinscst',
         'cofinsbase',
         'cofinsvalor',
         'csllbase',
         'csllpercentual',
         'csllvalor',
         'irpjbase',
         'irpjpercentual',
         'irpjvalor',
         'cofinspercentual',
         'codnotafiscalprodutobarraorigem',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
    ];


    // Chaves Estrangeiras
    public function NotaFiscalProdutoBarra()
    {
        return $this->belongsTo(NotaFiscalProdutoBarra::class, 'codnotafiscalprodutobarraorigem', 'codnotafiscalprodutobarra');
    }

    public function Cfop()
    {
        return $this->belongsTo(Cfop::class, 'codcfop', 'codcfop');
    }

    public function NegocioProdutoBarra()
    {
        return $this->belongsTo(NegocioProdutoBarra::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }

    public function NotaFiscal()
    {
        return $this->belongsTo(NotaFiscal::class, 'codnotafiscal', 'codnotafiscal');
    }

    public function ProdutoBarra()
    {
        return $this->belongsTo(ProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
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
    public function NotaFiscalProdutoBarraS()
    {
        return $this->hasMany(NotaFiscalProdutoBarra::class, 'codnotafiscalprodutobarra', 'codnotafiscalprodutobarraorigem');
    }

    public function EstoqueMovimentoS()
    {
        return $this->hasMany(EstoqueMovimento::class, 'codnotafiscalprodutobarra', 'codnotafiscalprodutobarra');
    }


}
