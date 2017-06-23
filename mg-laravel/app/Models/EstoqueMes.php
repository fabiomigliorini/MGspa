<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codestoquemes                      NOT NULL DEFAULT nextval('tblestoquemes_codestoquemes_seq'::regclass)
 * @property  bigint                         $codestoquesaldo                    NOT NULL
 * @property  date                           $mes                                NOT NULL
 * @property  numeric(14,3)                  $inicialquantidade                  
 * @property  numeric(14,2)                  $inicialvalor                       
 * @property  numeric(14,3)                  $entradaquantidade                  
 * @property  numeric(14,2)                  $entradavalor                       
 * @property  numeric(14,3)                  $saidaquantidade                    
 * @property  numeric(14,2)                  $saidavalor                         
 * @property  numeric(14,3)                  $saldoquantidade                    
 * @property  numeric(14,2)                  $saldovalor                         
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuariocriacao                  
 * @property  numeric(14,6)                  $customedio                         
 *
 * Chaves Estrangeiras
 * @property  EstoqueSaldo                   $EstoqueSaldo
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  EstoqueMovimento[]             $EstoqueMovimentoS
 */

class EstoqueMes extends MGModel
{
    protected $table = 'tblestoquemes';
    protected $primaryKey = 'codestoquemes';
    protected $fillable = [
          'codestoquesaldo',
         'mes',
         'inicialquantidade',
         'inicialvalor',
         'entradaquantidade',
         'entradavalor',
         'saidaquantidade',
         'saidavalor',
         'saldoquantidade',
         'saldovalor',
             'customedio',
    ];
    protected $dates = [
        'mes',
        'alteracao',
        'criacao',
    ];


    // Chaves Estrangeiras
    public function EstoqueSaldo()
    {
        return $this->belongsTo(EstoqueSaldo::class, 'codestoquesaldo', 'codestoquesaldo');
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
    public function EstoqueMovimentoS()
    {
        return $this->hasMany(EstoqueMovimento::class, 'codestoquemes', 'codestoquemes');
    }


}
