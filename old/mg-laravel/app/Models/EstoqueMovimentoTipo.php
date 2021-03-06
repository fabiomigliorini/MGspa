<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codestoquemovimentotipo            NOT NULL DEFAULT nextval('tblestoquemovimentotipo_codestoquemovimentotipo_seq'::regclass)
 * @property  varchar(100)                   $descricao                          NOT NULL
 * @property  varchar(3)                     $sigla                              NOT NULL
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuariocriacao                  
 * @property  smallint                       $preco                              NOT NULL
 * @property  bigint                         $codestoquemovimentotipoorigem      
 * @property  boolean                        $manual                             NOT NULL DEFAULT false
 * @property  boolean                        $atualizaultimaentrada              NOT NULL DEFAULT false
 * @property  boolean                        $transferencia                      NOT NULL DEFAULT false
 *
 * Chaves Estrangeiras
 * @property  EstoqueMovimentoTipo           $EstoqueMovimentoTipoOrigem
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  EstoqueMovimentoTipo[]         $EstoqueMovimentoTipoS
 * @property  NaturezaOperacao[]             $NaturezaOperacaoS
 * @property  EstoqueMovimento[]             $EstoqueMovimentoDestinoS
 */

class EstoqueMovimentoTipo extends MGModel
{
    protected $table = 'tblestoquemovimentotipo';
    protected $primaryKey = 'codestoquemovimentotipo';
    protected $fillable = [
          'descricao',
         'sigla',
             'preco',
         'codestoquemovimentotipoorigem',
         'manual',
         'atualizaultimaentrada',
         'transferencia',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
    ];

    const PRECO_INFORMADO = 1;
    const PRECO_MEDIO = 2;
    const PRECO_ORIGEM = 3;
    
    const AJUSTE = 1002;

    // Chaves Estrangeiras
    public function EstoqueMovimentoTipoOrigem()
    {
        return $this->belongsTo(EstoqueMovimentoTipo::class, 'codestoquemovimentotipoorigem', 'codestoquemovimentotipo');
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
    public function EstoqueMovimentoTipoDestinoS()
    {
        return $this->hasMany(EstoqueMovimentoTipo::class, 'codestoquemovimentotipoorigem', 'codestoquemovimentotipo');
    }

    public function NaturezaOperacaoS()
    {
        return $this->hasMany(NaturezaOperacao::class, 'codestoquemovimentotipo', 'codestoquemovimentotipo');
    }

    public function EstoqueMovimentoS()
    {
        return $this->hasMany(EstoqueMovimento::class, 'codestoquemovimentotipo', 'codestoquemovimentotipo');
    }


}
