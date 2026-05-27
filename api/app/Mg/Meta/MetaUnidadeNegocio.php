<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:42:42
 */

namespace Mg\Meta;

use Mg\MgModel;
use Mg\Meta\Meta;
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codmeta' => 'integer',
        'codmetaunidadenegocio' => 'integer',
        'codunidadenegocio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
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

}
