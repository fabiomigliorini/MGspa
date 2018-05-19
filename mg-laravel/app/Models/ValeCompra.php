<?php

namespace App\Models;

/**
 * Campos
 * @property  bigint                         $codvalecompra                      NOT NULL DEFAULT nextval('tblvalecompra_codvalecompra_seq'::regclass)
 * @property  bigint                         $codvalecompramodelo                NOT NULL
 * @property  bigint                         $codpessoafavorecido                NOT NULL
 * @property  bigint                         $codpessoa                          NOT NULL
 * @property  varchar(200)                   $observacoes                        
 * @property  varchar(50)                    $aluno                              NOT NULL
 * @property  varchar(30)                    $turma                              
 * @property  numeric(14,2)                  $totalprodutos                      
 * @property  numeric(14,2)                  $desconto                           
 * @property  numeric(14,2)                  $total                              
 * @property  bigint                         $codtitulo                          
 * @property  timestamp                      $inativo                            
 * @property  timestamp                      $criacao                            
 * @property  bigint                         $codusuariocriacao                  
 * @property  timestamp                      $alteracao                          
 * @property  bigint                         $codusuarioalteracao                
 * @property  bigint                         $codfilial                          NOT NULL
 *
 * Chaves Estrangeiras
 * @property  ValeCompraModelo               $ValeCompraModelo
 * @property  Titulo                         $Titulo
 * @property  Filial                         $Filial
 * @property  Pessoa                         $Pessoa
 * @property  Pessoa                         $Pessoa
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  ValeCompraFormaPagamento[]     $ValeCompraFormaPagamentoS
 * @property  ValeCompraProdutoBarra[]       $ValeCompraProdutoBarraS
 */

class ValeCompra extends MGModel
{
    protected $table = 'tblvalecompra';
    protected $primaryKey = 'codvalecompra';
    protected $fillable = [
          'codvalecompramodelo',
         'codpessoafavorecido',
         'codpessoa',
         'observacoes',
         'aluno',
         'turma',
         'totalprodutos',
         'desconto',
         'total',
         'codtitulo',
              'codfilial',
    ];
    protected $dates = [
        'inativo',
        'criacao',
        'alteracao',
    ];


    // Chaves Estrangeiras
    public function ValeCompraModelo()
    {
        return $this->belongsTo(ValeCompraModelo::class, 'codvalecompramodelo', 'codvalecompramodelo');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function PessoaFavorecido()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoafavorecido', 'codpessoa');
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
    public function ValeCompraFormaPagamentoS()
    {
        return $this->hasMany(ValeCompraFormaPagamento::class, 'codvalecompra', 'codvalecompra');
    }

    public function ValeCompraProdutoBarraS()
    {
        return $this->hasMany(ValeCompraProdutoBarra::class, 'codvalecompra', 'codvalecompra');
    }


}
