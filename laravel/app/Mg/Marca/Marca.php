<?php

namespace Mg\Marca;

/**
 * Campos
 * @property  bigint                         $codmarca                           NOT NULL DEFAULT nextval('tblmarca_codmarca_seq'::regclass)
 * @property  varchar(50)                    $marca
 * @property  boolean                        $site                               NOT NULL DEFAULT false
 * @property  varchar(1024)                  $descricaosite
 * @property  timestamp                      $alteracao
 * @property  bigint                         $codusuarioalteracao
 * @property  timestamp                      $criacao
 * @property  bigint                         $codusuariocriacao
 * @property  timestamp                      $inativo
 * @property  bigint                         $codimagem
 * @property  bigint                         $codopencart
 * @property  smallint                       $abccategoria                       NOT NULL DEFAULT 4
 * @property  bigint                         $abcposicao
 * @property  numeric(14,2)                  $vendaanovalor
 * @property  numeric(14,2)                  $vendabimestrevalor
 * @property  numeric(14,2)                  $vendasemestrevalor
 * @property  date                           $dataultimacompra
 * @property  smallint                       $itensabaixominimo
 * @property  smallint                       $itensacimamaximo
 * @property  smallint                       $estoqueminimodias                  NOT NULL DEFAULT 15
 * @property  smallint                       $estoquemaximodias                  NOT NULL DEFAULT 30
 * @property  boolean                        $abcignorar                         NOT NULL DEFAULT false
 * @property  numeric(5,2)                   $vendaanopercentual
 *
 * Chaves Estrangeiras
 * @property  Imagem                         $Imagem
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 * @property  ProdutoVariacao[]              $ProdutoVariacaoS
 * @property  ProdutoBarra[]                 $ProdutoBarraS
 * @property  Produto[]                      $ProdutoS
 */
use Mg\MgModel;
use Mg\Produto\Produto;
use Mg\Imagem\Imagem;

class Marca extends MGModel
{
    protected $table = 'tblmarca';
    protected $primaryKey = 'codmarca';
    protected $fillable = [
        'marca',
        'site',
        'descricaosite',
        'codimagem',
        'codopencart',
        'abccategoria',
        'abcposicao',
        'vendaanovalor',
        'vendabimestrevalor',
        'vendasemestrevalor',
        'dataultimacompra',
        'itensabaixominimo',
        'itensacimamaximo',
        'estoqueminimodias',
        'estoquemaximodias',
        'abcignorar',
        'vendaanopercentual',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
        'dataultimacompra',
    ];

    // Chaves Estrangeiras
    public function Imagem()
    {
        return $this->belongsTo(Imagem::class, 'codimagem', 'codimagem');
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
    public function ProdutoVariacaoS()
    {
        return $this->hasMany(ProdutoVariacao::class, 'codmarca', 'codmarca');
    }

    public function ProdutoBarraS()
    {
        return $this->hasMany(ProdutoBarra::class, 'codmarca', 'codmarca');
    }

    public function ProdutoS()
    {
        return $this->hasMany(Produto::class, 'codmarca', 'codmarca');
    }


}
