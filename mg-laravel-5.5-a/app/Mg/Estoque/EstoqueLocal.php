<?php

namespace Mg\Estoque;

/**
 * Campos
 * @property  bigint                         $codestoquelocal                    NOT NULL DEFAULT nextval('tblestoquelocal_codestoquelocal_seq'::regclass)
 * @property  varchar(50)                    $estoquelocal                       NOT NULL
 * @property  bigint                         $codfilial                          NOT NULL
 * @property  timestamp                      $inativo
 * @property  timestamp                      $alteracao
 * @property  bigint                         $codusuarioalteracao
 * @property  timestamp                      $criacao
 * @property  bigint                         $codusuariocriacao
 * @property  varchar(3)                     $sigla                              NOT NULL
 *
 * Chaves Estrangeiras
 * @property  Filial                         $Filial
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  EstoqueLocalProdutoVariacao[]  $EstoqueLocalProdutoVariacaoS
 * @property  Negocio[]                      $NegocioS
 * @property  NotaFiscal[]                   $NotaFiscalS
 */
 use Mg\MgModel;

class EstoqueLocal extends MgModel
{
    protected $table = 'tblestoquelocal';
    protected $primaryKey = 'codestoquelocal';
    protected $fillable = [
          'estoquelocal',
         'codfilial',
              'sigla',
    ];
    protected $dates = [
        'inativo',
        'alteracao',
        'criacao',
    ];


    // Chaves Estrangeiras
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
    public function EstoqueLocalProdutoVariacaoS()
    {
        return $this->hasMany(EstoqueLocalProdutoVariacao::class, 'codestoquelocal', 'codestoquelocal');
    }

    public function NegocioS()
    {
        return $this->hasMany(Negocio::class, 'codestoquelocal', 'codestoquelocal');
    }

    public function NotaFiscalS()
    {
        return $this->hasMany(NotaFiscal::class, 'codestoquelocal', 'codestoquelocal');
    }


}
