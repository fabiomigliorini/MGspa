<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Feb/2026 21:57:02
 */

namespace Mg\Filial;

use Mg\MgModel;
use Mg\Meta\BonificacaoEvento;
use Mg\Meta\MetaUnidadeNegocio;
use Mg\Meta\MetaUnidadeNegocioPessoa;
use Mg\Filial\Filial;

class UnidadeNegocio extends MgModel
{
    protected $table = 'tblunidadenegocio';
    protected $primaryKey = 'codunidadenegocio';


    protected $fillable = [
        'codfilial',
        'descricao',
        'inativo'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codfilial' => 'integer',
        'codunidadenegocio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }


    // Tabelas Filhas
    public function BonificacaoEventoS()
    {
        return $this->hasMany(BonificacaoEvento::class, 'codunidadenegocio', 'codunidadenegocio');
    }

    public function MetaUnidadeNegocioS()
    {
        return $this->hasMany(MetaUnidadeNegocio::class, 'codunidadenegocio', 'codunidadenegocio');
    }

    public function MetaUnidadeNegocioPessoaS()
    {
        return $this->hasMany(MetaUnidadeNegocioPessoa::class, 'codunidadenegocio', 'codunidadenegocio');
    }

}