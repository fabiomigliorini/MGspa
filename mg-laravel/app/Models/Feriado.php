<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codferiado                         NOT NULL DEFAULT nextval('tblferiado_codferiado_seq'::regclass)
 * @property  date                           $data                               NOT NULL
 * @property  varchar(100)                   $feriado                            NOT NULL
 * @property  bigint                         $codusuarioalteracao                
 * @property  timestamp                      $criacao                            
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuariocriacao                  
 *
 * Chaves Estrangeiras
 * @property  Usuario                        $UsuarioCriacao
 * @property  Usuario                        $UsuarioAlteracao
 *
 * Tabelas Filhas
 */

class Feriado extends MGModel
{
    protected $table = 'tblferiado';
    protected $primaryKey = 'codferiado';
    protected $fillable = [
          'data',
         'feriado',
        ];
    protected $dates = [
        'data',
        'criacao',
        'alteracao',
    ];


    // Chaves Estrangeiras
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
