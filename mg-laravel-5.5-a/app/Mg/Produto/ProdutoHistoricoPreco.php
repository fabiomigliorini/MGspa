<?php

namespace Mg\Produto;

/**
 * Campos
 * @property  bigint                         $codprodutohistoricopreco           NOT NULL DEFAULT nextval('tblprodutohistoricopreco_codprodutohistoricopreco_seq'::regclass)
 * @property  bigint                         $codproduto                         NOT NULL
 * @property  bigint                         $codprodutoembalagem
 * @property  numeric(14,2)                  $precoantigo
 * @property  numeric(14,2)                  $preconovo
 * @property  timestamp                      $alteracao
 * @property  bigint                         $codusuarioalteracao
 * @property  timestamp                      $criacao
 * @property  bigint                         $codusuariocriacao
 * @property  timestamp                      $inativo
 *
 * Chaves Estrangeiras
 * @property  Produto                        $Produto
 * @property  ProdutoEmbalagem               $ProdutoEmbalagem
 * @property  Usuario                        $UsuarioAlteracao
 * @property  Usuario                        $UsuarioCriacao
 *
 * Tabelas Filhas
 */
 use Mg\MgModel;

class ProdutoHistoricoPreco extends MgModel
{
    protected $table = 'tblprodutohistoricopreco';
    protected $primaryKey = 'codprodutohistoricopreco';
    protected $fillable = [
        'codproduto',
        'codprodutoembalagem',
        'precoantigo',
        'preconovo',
    ];
    protected $dates = [
        'alteracao',
        'criacao',
        'inativo',
    ];


    // Chaves Estrangeiras
    public function Produto()
    {
        return $this->belongsTo(Produto::class, 'codproduto', 'codproduto');
    }

    public function ProdutoEmbalagem()
    {
        return $this->belongsTo(ProdutoEmbalagem::class, 'codprodutoembalagem', 'codprodutoembalagem');
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

}
