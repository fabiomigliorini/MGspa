<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:25:13
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codncmtributacao' => 'integer',
        'codtributacao' => 'integer',
        'criacao' => 'datetime',
        'icmspercentualnorte' => 'float',
        'icmspercentualsul' => 'float'
    ];


    // Chaves Estrangeiras
    public function Tributacao()
    {
        return $this->belongsTo(Tributacao::class, 'codtributacao', 'codtributacao');
    }

}
