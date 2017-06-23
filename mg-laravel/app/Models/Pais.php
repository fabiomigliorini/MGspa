<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codpais                            NOT NULL DEFAULT nextval('tblpais_codpais_seq'::regclass)
 * @property  varchar(50)                    $pais                               
 * @property  varchar(2)                     $sigla                              
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuariocriacao                  
 * @property  bigint                         $codigooficial                      
 * @property  timestamp                      $inativo                            
 *
 * Chaves Estrangeiras
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  Estado[]                       $EstadoS
 */

class Pais extends MGModel
{
    protected $table = 'tblpais';
    protected $primaryKey = 'codpais';
    protected $fillable = [
          'pais',
         'sigla',
             'codigooficial',
     ];
    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
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


    // Tabelas Filhas
    public function EstadoS()
    {
        return $this->hasMany(Estado::class, 'codpais', 'codpais');
    }


}
