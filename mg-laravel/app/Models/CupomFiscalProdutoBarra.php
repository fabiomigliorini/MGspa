<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codcupomfiscalprodutobarra         NOT NULL DEFAULT nextval('tblcupomfiscalprodutobarra_codcupomfiscalprodutobarra_seq'::regclass)
 * @property  bigint                         $codcupomfiscal                     NOT NULL
 * @property  bigint                         $codprodutobarra                    NOT NULL
 * @property  varchar(10)                    $aliquotaicms                       
 * @property  numeric(14,2)                  $quantidade                         
 * @property  numeric(14,3)                  $valorunitario                      
 * @property  bigint                         $codnegocioprodutobarra             
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuariocriacao                  
 *
 * Chaves Estrangeiras
 * @property  Cupomfiscal                    $Cupomfiscal
 * @property  NegocioProdutoBarra            $NegocioProdutoBarra
 * @property  ProdutoBarra                   $ProdutoBarra
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 */

class CupomFiscalProdutoBarra extends MGModel
{
    protected $table = 'tblcupomfiscalprodutobarra';
    protected $primaryKey = 'codcupomfiscalprodutobarra';
    protected $fillable = [
          'codcupomfiscal',
         'codprodutobarra',
         'aliquotaicms',
         'quantidade',
         'valorunitario',
         'codnegocioprodutobarra',
        ];
    protected $dates = [
        'alteracao',
        'criacao',
    ];


    // Chaves Estrangeiras
    public function Cupomfiscal()
    {
        return $this->belongsTo(Cupomfiscal::class, 'codcupomfiscal', 'codcupomfiscal');
    }

    public function NegocioProdutoBarra()
    {
        return $this->belongsTo(NegocioProdutoBarra::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
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
