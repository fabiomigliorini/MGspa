<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codnfeterceiroitem                 NOT NULL DEFAULT nextval('tblnfeterceiroitem_codnfeterceiroitem_seq'::regclass)
 * @property  bigint                         $codnfeterceiro                     NOT NULL
 * @property  smallint                       $nitem                              
 * @property  varchar(30)                    $cprod                              
 * @property  varchar(200)                   $xprod                              
 * @property  varchar(30)                    $cean                               
 * @property  varchar(10)                    $ncm                                
 * @property  smallint                       $cfop                               
 * @property  varchar(10)                    $ucom                               
 * @property  numeric(14,2)                  $qcom                               
 * @property  numeric(14,2)                  $vuncom                             
 * @property  numeric(14,2)                  $vprod                              
 * @property  varchar(30)                    $ceantrib                           
 * @property  varchar(10)                    $utrib                              
 * @property  numeric(14,2)                  $qtrib                              
 * @property  numeric(14,2)                  $vuntrib                            
 * @property  varchar(10)                    $cst                                
 * @property  varchar(10)                    $csosn                              
 * @property  numeric(14,2)                  $vbc                                
 * @property  numeric(14,2)                  $picms                              
 * @property  numeric(14,2)                  $vicms                              
 * @property  numeric(14,2)                  $vbcst                              
 * @property  numeric(14,2)                  $picmsst                            
 * @property  numeric(14,2)                  $vicmsst                            
 * @property  numeric(14,2)                  $ipivbc                             
 * @property  numeric(14,2)                  $ipipipi                            
 * @property  numeric(14,2)                  $ipivipi                            
 * @property  bigint                         $codprodutobarra                    
 * @property  numeric(6,2)                   $margem                             
 * @property  numeric(14,2)                  $complemento                        
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuariocriacao                  
 *
 * Chaves Estrangeiras
 * @property  Nfeterceiro                    $Nfeterceiro
 * @property  ProdutoBarra                   $ProdutoBarra
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 */

class NfeTerceiroItem extends MGModel
{
    protected $table = 'tblnfeterceiroitem';
    protected $primaryKey = 'codnfeterceiroitem';
    protected $fillable = [
          'codnfeterceiro',
         'nitem',
         'cprod',
         'xprod',
         'cean',
         'ncm',
         'cfop',
         'ucom',
         'qcom',
         'vuncom',
         'vprod',
         'ceantrib',
         'utrib',
         'qtrib',
         'vuntrib',
         'cst',
         'csosn',
         'vbc',
         'picms',
         'vicms',
         'vbcst',
         'picmsst',
         'vicmsst',
         'ipivbc',
         'ipipipi',
         'ipivipi',
         'codprodutobarra',
         'margem',
         'complemento',
        ];
    protected $dates = [
        'alteracao',
        'criacao',
    ];


    // Chaves Estrangeiras
    public function Nfeterceiro()
    {
        return $this->belongsTo(Nfeterceiro::class, 'codnfeterceiro', 'codnfeterceiro');
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

}
