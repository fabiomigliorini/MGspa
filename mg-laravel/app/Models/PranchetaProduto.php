<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codpranchetaproduto                NOT NULL DEFAULT nextval('tblpranchetaproduto_codpranchetaproduto_seq'::regclass)
 * @property  bigint                         $codprancheta                       NOT NULL
 * @property  bigint                         $codproduto                         NOT NULL
 * @property  timestamp                      $criacao                            
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuariocriacao                  
 * @property  bigint                         $codusuarioalteracao                
 * @property  timestamp                      $inativo                            
 * @property  varchar(200)                   $observacoes                        
 *
 * Chaves Estrangeiras
 * @property  Usuario                        $UsuarioCriacao
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Prancheta                      $Prancheta
 * @property  Produto                        $Produto
 *
 * Tabelas Filhas
 */

class PranchetaProduto extends MGModel
{
    protected $table = 'tblpranchetaproduto';
    protected $primaryKey = 'codpranchetaproduto';
    protected $fillable = [
          'codprancheta',
         'codproduto',
              'observacoes',
    ];
    protected $dates = [
        'criacao',
        'alteracao',
        'inativo',
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

    public function Prancheta()
    {
        return $this->belongsTo(Prancheta::class, 'codprancheta', 'codprancheta');
    }

    public function Produto()
    {
        return $this->belongsTo(Produto::class, 'codproduto', 'codproduto');
    }


    // Tabelas Filhas

}
