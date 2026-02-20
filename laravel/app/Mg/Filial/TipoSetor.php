<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Feb/2026 16:58:02
 */

namespace Mg\Filial;

use Mg\MgModel;
use Mg\Produto\Produto;
use Mg\Filial\Setor;

class TipoSetor extends MgModel
{
    protected $table = 'tbltiposetor';
    protected $primaryKey = 'codtiposetor';


    protected $fillable = [
        'inativo',
        'tiposetor'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codtiposetor' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Tabelas Filhas
    public function ProdutoS()
    {
        return $this->hasMany(Produto::class, 'codtiposetor', 'codtiposetor');
    }

    public function SetorS()
    {
        return $this->hasMany(Setor::class, 'codtiposetor', 'codtiposetor');
    }

}