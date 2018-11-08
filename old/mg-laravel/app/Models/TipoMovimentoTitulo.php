<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codtipomovimentotitulo             NOT NULL DEFAULT nextval('tbltipomovimentotitulo_codtipomovimentotitulo_seq'::regclass)
 * @property  varchar(20)                    $tipomovimentotitulo                
 * @property  boolean                        $implantacao                        NOT NULL DEFAULT false
 * @property  boolean                        $ajuste                             NOT NULL DEFAULT false
 * @property  boolean                        $armotizacao                        NOT NULL DEFAULT false
 * @property  boolean                        $juros                              NOT NULL DEFAULT false
 * @property  boolean                        $desconto                           NOT NULL DEFAULT false
 * @property  boolean                        $pagamento                          NOT NULL DEFAULT false
 * @property  boolean                        $estorno                            NOT NULL DEFAULT false
 * @property  varchar(255)                   $observacao                         
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuariocriacao                  
 *
 * Chaves Estrangeiras
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  MovimentoTitulo[]              $MovimentoTituloS
 * @property  Tipotitulo[]                   $TipotituloS
 */

class TipoMovimentoTitulo extends MGModel
{
    protected $table = 'tbltipomovimentotitulo';
    protected $primaryKey = 'codtipomovimentotitulo';
    protected $fillable = [
          'tipomovimentotitulo',
         'implantacao',
         'ajuste',
         'armotizacao',
         'juros',
         'desconto',
         'pagamento',
         'estorno',
         'observacao',
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
    public function MovimentoTituloS()
    {
        return $this->hasMany(MovimentoTitulo::class, 'codtipomovimentotitulo', 'codtipomovimentotitulo');
    }

    public function TipotituloS()
    {
        return $this->hasMany(Tipotitulo::class, 'codtipomovimentotitulo', 'codtipomovimentotitulo');
    }


}
