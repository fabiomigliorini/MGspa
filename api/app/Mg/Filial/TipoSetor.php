<?php

namespace Mg\Filial;

use Mg\MgModel;

class TipoSetor extends MgModel
{
    protected $table = 'tbltiposetor';
    protected $primaryKey = 'codtiposetor';

    protected $fillable = [
        'inativo',
        'tiposetor',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'codtiposetor' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
    ];

    public function SetorS()
    {
        return $this->hasMany(Setor::class, 'codtiposetor', 'codtiposetor');
    }
}
