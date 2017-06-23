<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codnegocioprodutobarra             NOT NULL DEFAULT nextval('tblnegocioprodutobarra_codnegocioprodutobarra_seq'::regclass)
 * @property  bigint                         $codnegocio                         NOT NULL
 * @property  numeric(14,3)                  $quantidade                         NOT NULL
 * @property  numeric(14,3)                  $valorunitario                      NOT NULL
 * @property  numeric(14,2)                  $valortotal                         NOT NULL
 * @property  bigint                         $codprodutobarra                    NOT NULL
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuariocriacao                  
 * @property  bigint                         $codnegocioprodutobarradevolucao    
 * @property  timestamp                      $inativo                            
 *
 * Chaves Estrangeiras
 * @property  NegocioProdutoBarra            $NegocioProdutoBarra
 * @property  Negocio                        $Negocio
 * @property  ProdutoBarra                   $ProdutoBarra
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  NegocioProdutoBarra[]          $NegocioProdutoBarraS
 * @property  Cupomfiscalprodutobarra[]      $CupomfiscalprodutobarraS
 * @property  EstoqueMovimento[]             $EstoqueMovimentoS
 * @property  NotaFiscalProdutoBarra[]       $NotaFiscalProdutoBarraS
 */

class NegocioProdutoBarra extends MGModel
{
    protected $table = 'tblnegocioprodutobarra';
    protected $primaryKey = 'codnegocioprodutobarra';
    protected $fillable = [
        'codnegocio',
        'quantidade',
        'valorunitario',
        'valortotal',
        'codprodutobarra',
        'codnegocioprodutobarradevolucao',
     ];
    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
    ];


    // Chaves Estrangeiras
    public function NegocioProdutoBarra()
    {
        return $this->belongsTo(NegocioProdutoBarra::class, 'codnegocioprodutobarradevolucao', 'codnegocioprodutobarra');
    }

    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
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
    public function NegocioProdutoBarraS()
    {
        return $this->hasMany(NegocioProdutoBarra::class, 'codnegocioprodutobarra', 'codnegocioprodutobarradevolucao');
    }

    public function CupomfiscalprodutobarraS()
    {
        return $this->hasMany(Cupomfiscalprodutobarra::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }

    public function EstoqueMovimentoS()
    {
        return $this->hasMany(EstoqueMovimento::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }

    public function NotaFiscalProdutoBarraS()
    {
        return $this->hasMany(NotaFiscalProdutoBarra::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }
}
