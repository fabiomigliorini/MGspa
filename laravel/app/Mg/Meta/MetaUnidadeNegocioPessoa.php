<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Feb/2026 21:57:15
 */

namespace Mg\Meta;

use Mg\MgModel;
use Mg\Meta\Meta;
use Mg\Pessoa\Pessoa;
use Mg\Filial\UnidadeNegocio;

class MetaUnidadeNegocioPessoa extends MgModel
{
    protected $table = 'tblmetaunidadenegociopessoa';
    protected $primaryKey = 'codmetaunidadenegociopessoa';


    protected $fillable = [
        'codmeta',
        'codpessoa',
        'codunidadenegocio',
        'datainicial',
        'datafinal',
        'percentualcaixa',
        'percentualsubgerente',
        'percentualvenda',
        'percentualxerox'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'datainicial',
        'datafinal'
    ];

    protected $casts = [
        'codmeta' => 'integer',
        'codmetaunidadenegociopessoa' => 'integer',
        'codpessoa' => 'integer',
        'codunidadenegocio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'percentualcaixa' => 'float',
        'percentualsubgerente' => 'float',
        'percentualvenda' => 'float',
        'percentualxerox' => 'float'
    ];


    // Chaves Estrangeiras
    public function Meta()
    {
        return $this->belongsTo(Meta::class, 'codmeta', 'codmeta');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function UnidadeNegocio()
    {
        return $this->belongsTo(UnidadeNegocio::class, 'codunidadenegocio', 'codunidadenegocio');
    }

}