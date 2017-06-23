<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codcargo                           NOT NULL DEFAULT nextval('tblcargo_codcargo_seq'::regclass)
 * @property  varchar(50)                    $cargo                              NOT NULL
 * @property  timestamp                      $inativo                            
 * @property  timestamp                      $criacao                            
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  bigint                         $codusuariocriacao                  
 *
 * Chaves Estrangeiras
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  MetaFilialPessoa[]             $MetaFilialPessoaS
 */

class Cargo extends MGModel
{
    protected $table = 'tblcargo';
    protected $primaryKey = 'codcargo';
    protected $fillable = [
          'cargo',
         ];
    protected $dates = [
        'inativo',
        'criacao',
        'alteracao',
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
    public function MetaFilialPessoaS()
    {
        return $this->hasMany(MetaFilialPessoa::class, 'codcargo', 'codcargo');
    }


}
