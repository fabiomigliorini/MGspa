<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:34:09
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Produto\GrupoProduto;
use Mg\Imagem\Imagem;
use Mg\Produto\SecaoProduto;
use Mg\Usuario\Usuario;

class FamiliaProduto extends MgModel
{
    protected $table = 'tblfamiliaproduto';
    protected $primaryKey = 'codfamiliaproduto';


    protected $fillable = [
        'codimagem',
        'codopencart',
        'codsecaoproduto',
        'familiaproduto',
        'inativo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codfamiliaproduto' => 'integer',
        'codimagem' => 'integer',
        'codopencart' => 'integer',
        'codsecaoproduto' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function Imagem()
    {
        return $this->belongsTo(Imagem::class, 'codimagem', 'codimagem');
    }

    public function SecaoProduto()
    {
        return $this->belongsTo(SecaoProduto::class, 'codsecaoproduto', 'codsecaoproduto');
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
    public function GrupoProdutoS()
    {
        return $this->hasMany(GrupoProduto::class, 'codfamiliaproduto', 'codfamiliaproduto');
    }

}
