<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Feb/2026 21:57:08
 */

namespace Mg\Meta;

use Mg\MgModel;
use Mg\Meta\Meta;
use Mg\Meta\MetaUnidadeNegocioPessoa;
use Mg\Meta\MetaUnidadeNegocioPessoaFixo;
use Mg\Filial\UnidadeNegocio;

class MetaUnidadeNegocio extends MgModel
{
    protected $table = 'tblmetaunidadenegocio';
    protected $primaryKey = 'codmetaunidadenegocio';


    protected $fillable = [
        'codmeta',
        'codunidadenegocio',
        'percentualcomissaosubgerente',
        'percentualcomissaosubgerentemeta',
        'percentualcomissaovendedor',
        'percentualcomissaovendedormeta',
        'percentualcomissaoxerox',
        'premiometaxerox',
        'premioprimeirovendedor',
        'premiosubgerentemeta',
        'valormeta',
        'valormetacaixa',
        'valormetavendedor',
        'valormetaxerox'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codmeta' => 'integer',
        'codmetaunidadenegocio' => 'integer',
        'codunidadenegocio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'percentualcomissaosubgerente' => 'float',
        'percentualcomissaosubgerentemeta' => 'float',
        'percentualcomissaovendedor' => 'float',
        'percentualcomissaovendedormeta' => 'float',
        'percentualcomissaoxerox' => 'float',
        'premiometaxerox' => 'float',
        'premioprimeirovendedor' => 'float',
        'premiosubgerentemeta' => 'float',
        'valormeta' => 'float',
        'valormetacaixa' => 'float',
        'valormetavendedor' => 'float',
        'valormetaxerox' => 'float'
    ];


    // Chaves Estrangeiras
    public function Meta()
    {
        return $this->belongsTo(Meta::class, 'codmeta', 'codmeta');
    }

    public function UnidadeNegocio()
    {
        return $this->belongsTo(UnidadeNegocio::class, 'codunidadenegocio', 'codunidadenegocio');
    }


    // Tabelas Filhas
    public function MetaUnidadeNegocioPessoaS()
    {
        return $this->hasMany(MetaUnidadeNegocioPessoa::class, 'codunidadenegocio', 'codunidadenegocio')
            ->where('codmeta', $this->codmeta);
    }

    public function MetaUnidadeNegocioPessoaFixoS()
    {
        return $this->hasMany(MetaUnidadeNegocioPessoaFixo::class, 'codunidadenegocio', 'codunidadenegocio')
            ->where('codmeta', $this->codmeta);
    }

}