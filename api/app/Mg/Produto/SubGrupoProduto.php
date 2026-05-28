<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:33:25
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Produto\Produto;
use Mg\Produto\GrupoProduto;
use Mg\Imagem\Imagem;
use Mg\Usuario\Usuario;

class SubGrupoProduto extends MgModel
{
    protected $table = 'tblsubgrupoproduto';
    protected $primaryKey = 'codsubgrupoproduto';


    protected $fillable = [
        'codgrupoproduto',
        'codimagem',
        'codopencart',
        'inativo',
        'subgrupoproduto'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codgrupoproduto' => 'integer',
        'codimagem' => 'integer',
        'codopencart' => 'integer',
        'codsubgrupoproduto' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function GrupoProduto()
    {
        return $this->belongsTo(GrupoProduto::class, 'codgrupoproduto', 'codgrupoproduto');
    }

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
    public function ProdutoS()
    {
        return $this->hasMany(Produto::class, 'codsubgrupoproduto', 'codsubgrupoproduto');
    }

}
