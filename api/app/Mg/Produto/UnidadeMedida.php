<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/May/2021 15:31:47
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Produto\Produto;
use Mg\Produto\ProdutoEmbalagem;
use Mg\Usuario\Usuario;

class UnidadeMedida extends MgModel
{
    protected $table = 'tblunidademedida';
    protected $primaryKey = 'codunidademedida';


    protected $fillable = [
        'inativo',
        'sigla',
        'unidademedida'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codunidademedida' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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
    public function ProdutoS()
    {
        return $this->hasMany(Produto::class, 'codunidademedida', 'codunidademedida');
    }

    public function ProdutoEmbalagemS()
    {
        return $this->hasMany(ProdutoEmbalagem::class, 'codunidademedida', 'codunidademedida');
    }

}