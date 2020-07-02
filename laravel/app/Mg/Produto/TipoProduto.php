<?php
/**
 * Created by php artisan gerador:model.
 * Date: 02/Jul/2020 09:16:18
 */

namespace Mg\Produto;

use Mg\MgModel;
use Mg\Produto\Produto;
use Mg\NaturezaOperacao\TributacaoNaturezaOperacao;
use Mg\Usuario\Usuario;

class TipoProduto extends MgModel
{
    protected $table = 'tbltipoproduto';
    protected $primaryKey = 'codtipoproduto';


    protected $fillable = [
        'estoque',
        'tipoproduto'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codtipoproduto' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'estoque' => 'boolean'
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
        return $this->hasMany(Produto::class, 'codtipoproduto', 'codtipoproduto');
    }

    public function TributacaoNaturezaOperacaoS()
    {
        return $this->hasMany(TributacaoNaturezaOperacao::class, 'codtipoproduto', 'codtipoproduto');
    }

}