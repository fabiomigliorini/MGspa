<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/May/2021 15:21:52
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Produto\SubGrupoProduto;
use Mg\Produto\FamiliaProduto;
use Mg\Imagem\Imagem;
use Mg\Usuario\Usuario;

class GrupoProduto extends MgModel
{
    protected $table = 'tblgrupoproduto';
    protected $primaryKey = 'codgrupoproduto';


    protected $fillable = [
        'codfamiliaproduto',
        'codimagem',
        'codopencart',
        'grupoproduto',
        'inativo'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codfamiliaproduto' => 'integer',
        'codgrupoproduto' => 'integer',
        'codimagem' => 'integer',
        'codopencart' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function FamiliaProduto()
    {
        return $this->belongsTo(FamiliaProduto::class, 'codfamiliaproduto', 'codfamiliaproduto');
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
    public function SubGrupoProdutoS()
    {
        return $this->hasMany(SubGrupoProduto::class, 'codgrupoproduto', 'codgrupoproduto');
    }

}