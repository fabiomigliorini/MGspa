<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:34:35
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codunidademedida' => 'integer',
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
    public function ProdutoS()
    {
        return $this->hasMany(Produto::class, 'codunidademedida', 'codunidademedida');
    }

    public function ProdutoEmbalagemS()
    {
        return $this->hasMany(ProdutoEmbalagem::class, 'codunidademedida', 'codunidademedida');
    }

}
