<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codprancheta                       NOT NULL DEFAULT nextval('tblprancheta_codprancheta_seq'::regclass)
 * @property  varchar(50)                    $prancheta                          NOT NULL
 * @property  timestamp                      $alteracao                          
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuarioalteracao                
 * @property  bigint                         $codusuariocriacao                  
 * @property  varchar(200)                   $observacoes                        
 *
 * Chaves Estrangeiras
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  PranchetaProduto[]        $PranchetaProdutoS
 */

class Prancheta extends MGModel
{
    protected $table = 'tblprancheta';
    protected $primaryKey = 'codprancheta';
    protected $fillable = [
          'prancheta',
             'observacoes',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
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
    public function PranchetaProdutoS()
    {
        return $this->hasMany(PranchetaProduto::class, 'codprancheta', 'codprancheta');
    }


}
