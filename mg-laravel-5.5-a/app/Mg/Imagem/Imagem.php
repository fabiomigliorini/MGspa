<?php

namespace Mg\Imagem;

/**
 * Campos
 * @property  bigint                         $codimagem                          NOT NULL DEFAULT nextval('tblimagem_codimagem_seq'::regclass)
 * @property  varchar(200)                   $observacoes
 * @property  timestamp                      $inativo
 * @property  timestamp                      $criacao
 * @property  bigint                         $codusuariocriacao
 * @property  timestamp                      $alteracao
 * @property  bigint                         $codusuarioalteracao
 * @property  varchar(150)                   $arquivo
 *
 * Chaves Estrangeiras
 * @property  Usuario                        $UsuarioCriacao
 * @property  Usuario                        $UsuarioAlteracao
 *
 * Tabelas Filhas
 * @property  FamiliaProduto[]               $FamiliaProdutoS
 * @property  Usuario[]                      $UsuarioS
 * @property  GrupoProduto[]                 $GrupoProdutoS
 * @property  Marca[]                        $MarcaS
 * @property  ProdutoImagem[]                $ProdutoImagemS
 * @property  SecaoProduto[]                 $SecaoProdutoS
 * @property  SubGrupoProduto[]              $SubGrupoProdutoS
 *
 * Relacionamentos N x N
 * @property  Produto[]                       $ProdutoS
 *
 */

use Mg\MgModel;
use Mg\Usuario\Usuario;
use Mg\Marca\Marca;
use Carbon\Carbon;

class Imagem extends MgModel
{

    protected $table = 'tblimagem';
    protected $primaryKey = 'codimagem';
    protected $fillable = [
          'observacoes',
              'arquivo',
    ];
    protected $dates = [
        'inativo',
        'criacao',
        'alteracao',
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


    // Tabelas Filhas
    public function UsuarioS()
    {
        return $this->hasMany(Usuario::class, 'codimagem', 'codimagem');
    }

    public function FamiliaProdutoS()
    {
        return $this->hasMany(FamiliaProduto::class, 'codimagem', 'codimagem');
    }

    public function GrupoProdutoS()
    {
        return $this->hasMany(GrupoProduto::class, 'codimagem', 'codimagem');
    }

    public function MarcaS()
    {
        return $this->hasMany(Marca::class, 'codimagem', 'codimagem');
    }

    public function ProdutoImagemS()
    {
        return $this->hasMany(ProdutoImagem::class, 'codimagem', 'codimagem');
    }

    public function SecaoProdutoS()
    {
        return $this->hasMany(SecaoProduto::class, 'codimagem', 'codimagem');
    }

    public function SubGrupoProdutoS()
    {
        return $this->hasMany(SubGrupoProduto::class, 'codimagem', 'codimagem');
    }

    // Relacionamento N x N
    public function ProdutoS()
    {
        return $this->belongsToMany(Produto::class, 'tblprodutoimagem', 'codimagem', 'codproduto');
    }

    // Campos calculados
    public function getUrlAttribute()
    {
        return url(asset("imagens/{$this->arquivo}"));
    }

    public function getPathAttribute()
    {
        return "$this->directory/{$this->arquivo}";
    }

    public function getDirectoryAttribute()
    {
        return public_path("imagens");
    }
}
