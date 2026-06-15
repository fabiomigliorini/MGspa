<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 19:33:34
 */

namespace Mg\Fazenda;

use Mg\MgModel;
use Mg\Fazenda\Talhao;
use Mg\Pessoa\Pessoa;

class Fazenda extends MgModel
{
    protected $table = 'tblfazenda';
    protected $primaryKey = 'codfazenda';



    protected $fillable = [
        'areatotal',
        'codpessoa',
        'fazenda',
        'inativo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'areatotal' => 'float',
        'codfazenda' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }


    // Tabelas Filhas
    public function TalhaoS()
    {
        return $this->hasMany(Talhao::class, 'codfazenda', 'codfazenda');
    }

}
