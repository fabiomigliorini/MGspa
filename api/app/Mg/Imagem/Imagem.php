<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:33:00
 */

namespace Mg\Imagem;

use Mg\MgModel;
use Mg\Produto\FamiliaProduto;
use Mg\Produto\GrupoProduto;
use Mg\Marca\Marca;
use Mg\Mercos\MercosProdutoImagem;
use Mg\Produto\ProdutoImagem;
use Mg\Produto\SecaoProduto;
use Mg\Produto\SubGrupoProduto;
use Mg\Usuario\Usuario;

class Imagem extends MgModel
{
    protected $table = 'tblimagem';
    protected $primaryKey = 'codimagem';


    protected $fillable = [
        'arquivo',
        'inativo',
        'observacoes'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codimagem' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
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

    public function MercosProdutoImagemS()
    {
        return $this->hasMany(MercosProdutoImagem::class, 'codimagem', 'codimagem');
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

    public function UsuarioS()
    {
        return $this->hasMany(Usuario::class, 'codimagem', 'codimagem');
    }


    // Customizado
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
