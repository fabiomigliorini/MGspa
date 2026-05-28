<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:42:55
 */

namespace Mg\Filial;

use Mg\MgModel;
use Mg\Meta\BonificacaoEvento;
use Mg\Rh\Indicador;
use Mg\Meta\MetaUnidadeNegocio;
use Mg\Meta\MetaUnidadeNegocioPessoa;
use Mg\Meta\MetaUnidadeNegocioPessoaFixo\MetaUnidadeNegocioPessoaFixo;
use Mg\Filial\Setor;
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codfilial' => 'integer',
        'codunidadenegocio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
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

    public function IndicadorS()
    {
        return $this->hasMany(Indicador::class, 'codunidadenegocio', 'codunidadenegocio');
    }

    public function MetaUnidadeNegocioS()
    {
        return $this->hasMany(MetaUnidadeNegocio::class, 'codunidadenegocio', 'codunidadenegocio');
    }

    public function MetaUnidadeNegocioPessoaS()
    {
        return $this->hasMany(MetaUnidadeNegocioPessoa::class, 'codunidadenegocio', 'codunidadenegocio');
    }

    public function MetaUnidadeNegocioPessoaFixoS()
    {
        return $this->hasMany(MetaUnidadeNegocioPessoaFixo::class, 'codunidadenegocio', 'codunidadenegocio');
    }

    public function SetorS()
    {
        return $this->hasMany(Setor::class, 'codunidadenegocio', 'codunidadenegocio');
    }

}
