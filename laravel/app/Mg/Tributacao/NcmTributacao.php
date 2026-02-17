<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Feb/2026 10:26:12
 */

namespace Mg\Tributacao;

use Mg\MgModel;
use Mg\Tributacao\Tributacao;

class NcmTributacao extends MgModel
{
    protected $table = 'tblncmtributacao';
    protected $primaryKey = 'codncmtributacao';


    protected $fillable = [
        'codtributacao',
        'descricao',
        'icmspercentualnorte',
        'icmspercentualsul',
        'ncm',
        'ncmexcecao',
        'subitem'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codncmtributacao' => 'integer',
        'codtributacao' => 'integer',
        'icmspercentualnorte' => 'float',
        'icmspercentualsul' => 'float'
    ];


    // Chaves Estrangeiras
    public function Tributacao()
    {
        return $this->belongsTo(Tributacao::class, 'codtributacao', 'codtributacao');
    }

}