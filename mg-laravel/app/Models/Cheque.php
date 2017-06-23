<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codcheque                          NOT NULL DEFAULT nextval('tblcheque_codcheque_seq'::regclass)
 * @property  varchar(50)                    $cmc7                               
 * @property  bigint                         $codbanco                           NOT NULL
 * @property  varchar(10)                    $agencia                            NOT NULL
 * @property  varchar(15)                    $contacorrente                      NOT NULL
 * @property  varchar(100)                   $emitente                           
 * @property  varchar(15)                    $numero                             NOT NULL
 * @property  date                           $emissao                            NOT NULL
 * @property  date                           $vencimento                         NOT NULL
 * @property  date                           $repasse                            
 * @property  varchar(50)                    $destino                            
 * @property  date                           $devolucao                          
 * @property  varchar(50)                    $motivodevolucao                    
 * @property  varchar(200)                   $observacao                         
 * @property  timestamp                      $lancamento                         
 * @property  timestamp                      $alteracao                          
 * @property  timestamp                      $cancelamento                       
 * @property  numeric(14,2)                  $valor                              NOT NULL
 * @property  bigint                         $codusuarioalteracao                
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuariocriacao                  
 * @property  bigint                         $codpessoa                          NOT NULL
 * @property  smallint                       $indstatus                          NOT NULL DEFAULT 1
 * @property  bigint                         $codtitulo                          
 * @property  timestamp                      $inativo                            
 *
 * Chaves Estrangeiras
 * @property  Pessoa                         $Pessoa
 * @property  Titulo                         $Titulo
 * @property  Banco                          $Banco
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  ChequeRepasseCheque[]          $ChequeRepasseChequeS
 * @property  ChequeEmitente[]               $ChequeEmitenteS
 * @property  Cobranca[]                     $CobrancaS
 */

class Cheque extends MGModel
{
    protected $table = 'tblcheque';
    protected $primaryKey = 'codcheque';
    protected $fillable = [
          'cmc7',
         'codbanco',
         'agencia',
         'contacorrente',
         'emitente',
         'numero',
         'emissao',
         'vencimento',
         'repasse',
         'destino',
         'devolucao',
         'motivodevolucao',
         'observacao',
         'lancamento',
          'cancelamento',
         'valor',
            'codpessoa',
         'indstatus',
         'codtitulo',
     ];
    protected $dates = [
        'emissao',
        'vencimento',
        'repasse',
        'devolucao',
        'lancamento',
        'alteracao',
        'cancelamento',
        'criacao',
        'inativo',
    ];


    // Chaves Estrangeiras
    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
    }

    public function Banco()
    {
        return $this->belongsTo(Banco::class, 'codbanco', 'codbanco');
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
    public function ChequeRepasseChequeS()
    {
        return $this->hasMany(ChequeRepasseCheque::class, 'codcheque', 'codcheque');
    }

    public function ChequeEmitenteS()
    {
        return $this->hasMany(ChequeEmitente::class, 'codcheque', 'codcheque');
    }

    public function CobrancaS()
    {
        return $this->hasMany(Cobranca::class, 'codcheque', 'codcheque');
    }


}
