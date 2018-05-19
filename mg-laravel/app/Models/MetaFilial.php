<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codmetafilial                      NOT NULL DEFAULT nextval('tblmetafilial_codmetafilial_seq'::regclass)
 * @property  bigint                         $codmeta                            NOT NULL
 * @property  bigint                         $codfilial                          NOT NULL
 * @property  numeric(14,2)                  $valormetafilial                    NOT NULL
 * @property  numeric(14,2)                  $valormetavendedor                  NOT NULL
 * @property  text                           $observacoes                        
 * @property  timestamp                      $criacao                            
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  bigint                         $codusuariocriacao                  
 *
 * Chaves Estrangeiras
 * @property  Meta                           $Meta
 * @property  Filial                         $Filial
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  MetaFilialPessoa[]             $MetaFilialPessoaS
 */

class MetaFilial extends MGModel
{
    protected $table = 'tblmetafilial';
    protected $primaryKey = 'codmetafilial';
    protected $fillable = [
          'codmeta',
         'codfilial',
         'valormetafilial',
         'valormetavendedor',
         'observacoes',
        ];
    protected $dates = [
        'criacao',
        'alteracao',
    ];


    // Chaves Estrangeiras
    public function Meta()
    {
        return $this->belongsTo(Meta::class, 'codmeta', 'codmeta');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
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
    public function MetaFilialPessoaS()
    {
        return $this->hasMany(MetaFilialPessoa::class, 'codmetafilial', 'codmetafilial');
    }


}
