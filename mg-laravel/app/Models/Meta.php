<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codmeta                            NOT NULL DEFAULT nextval('tblmeta_codmeta_seq'::regclass)
 * @property  timestamp                      $periodoinicial                     NOT NULL
 * @property  timestamp                      $periodofinal                       NOT NULL
 * @property  numeric(14,2)                  $premioprimeirovendedorfilial       NOT NULL
 * @property  numeric(4,2)                   $percentualcomissaovendedor         NOT NULL
 * @property  numeric(4,2)                   $percentualcomissaovendedormeta     NOT NULL
 * @property  numeric(4,2)                   $percentualcomissaosubgerentemeta   NOT NULL
 * @property  text                           $observacoes                        
 * @property  timestamp                      $criacao                            
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  bigint                         $codusuariocriacao                  
 * @property  numeric(4,2)                   $percentualcomissaoxerox            
 *
 * Chaves Estrangeiras
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  MetaFilial[]                   $MetaFilialS
 */

class Meta extends MGModel
{
    protected $table = 'tblmeta';
    protected $primaryKey = 'codmeta';
    protected $fillable = [
        'periodoinicial',
        'periodofinal',
        'premioprimeirovendedorfilial',
        'percentualcomissaovendedor',
        'percentualcomissaovendedormeta',
        'percentualcomissaosubgerentemeta',
        'observacoes',
        'percentualcomissaoxerox',
    ];
    protected $dates = [
        'periodoinicial',
        'periodofinal',
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
    public function MetaFilialS()
    {
        return $this->hasMany(MetaFilial::class, 'codmeta', 'codmeta');
    }


}
