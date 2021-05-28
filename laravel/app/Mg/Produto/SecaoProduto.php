<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/May/2021 15:21:38
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Produto\FamiliaProduto;
use Mg\Imagem\Imagem;
use Mg\Usuario\Usuario;

class SecaoProduto extends MgModel
{
    protected $table = 'tblsecaoproduto';
    protected $primaryKey = 'codsecaoproduto';


    protected $fillable = [
        'codimagem',
        'codopencart',
        'inativo',
        'secaoproduto'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codimagem' => 'integer',
        'codopencart' => 'integer',
        'codsecaoproduto' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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
    public function FamiliaProdutoS()
    {
        return $this->hasMany(FamiliaProduto::class, 'codsecaoproduto', 'codsecaoproduto');
    }

}