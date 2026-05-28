<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:43:01
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codtiposetor' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
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
