<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mg\Estoque;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

/**
 * Campos
 * @property  bigint                         $codestoquesaldo                    NOT NULL DEFAULT nextval('tblestoquesaldo_codestoquesaldo_seq'::regclass)
 * @property  bigint                         $codestoquelocalprodutovariacao             NOT NULL
 * @property  boolean                        $fiscal                             NOT NULL
 * @property  numeric(14,3)                  $saldoquantidade
 * @property  numeric(14,2)                  $saldovalor
 * @property  numeric(14,6)                  $customedio
 * @property  timestamp                      $dataentrada
 * @property  timestamp                      $ultimaconferencia
 * @property  timestamp                      $alteracao
 * @property  bigint                         $codusuarioalteracao
 * @property  timestamp                      $criacao
 * @property  bigint                         $codusuariocriacao
 * @property  bigint                         $codestoquelocal                    NOT NULL
 *
 * Chaves Estrangeiras
 * @property  Produto                        $Produto
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 * @property  EstoqueLocalProdutoVariacao    $EstoqueLocalProdutoVariacao
 *
 * Tabelas Filhas
 * @property  EstoqueMes[]                   $EstoqueMesS
 * @property  EstoqueSaldoConferencia[]      $EstoqueSaldoConferenciaS
 */
 use Mg\MgModel;
 
class EstoqueSaldo extends MgModel
{
    protected $table = 'tblestoquesaldo';
    protected $primaryKey = 'codestoquesaldo';
    protected $fillable = [
        'fiscal',
        'saldoquantidade',
        'saldovalor',
        'customedio',
        'codestoquelocalprodutovariacao',
        'ultimaconferencia',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
        'ultimaconferencia',
        'dataentrada',
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

    public function EstoqueLocalProdutoVariacao()
    {
        return $this->belongsTo(EstoqueLocalProdutoVariacao::class, 'codestoquelocalprodutovariacao', 'codestoquelocalprodutovariacao');
    }


    // Tabelas Filhas
    public function EstoqueMesS()
    {
        return $this->hasMany(EstoqueMes::class, 'codestoquesaldo', 'codestoquesaldo');
    }

    public function EstoqueSaldoConferenciaS()
    {
        return $this->hasMany(EstoqueSaldoConferencia::class, 'codestoquesaldo', 'codestoquesaldo');
    }

    public function scopeFiscal($query) {
        $query->where("{$this->table}.fiscal", true);
    }

    public function scopeFisico($query) {
        $query->where("{$this->table}.fiscal", false);
    }

}
