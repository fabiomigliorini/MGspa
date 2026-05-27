<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:31:44
 */

namespace Mg\NotaFiscal;

use Mg\MgModel;
use Mg\NotaFiscal\NotaFiscal;

class NotaFiscalCreditoIcmsSimples extends MgModel
{
    protected $table = 'tblnotafiscalcreditoicmssimples';
    protected $primaryKey = 'codnotafiscalcreditoicmssimples';


    protected $fillable = [
        'codnotafiscal',
        'nfechave',
        'percentual'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codnotafiscal' => 'integer',
        'codnotafiscalcreditoicmssimples' => 'integer',
        'criacao' => 'datetime',
        'percentual' => 'float'
    ];


    // Chaves Estrangeiras
    public function NotaFiscal()
    {
        return $this->belongsTo(NotaFiscal::class, 'codnotafiscal', 'codnotafiscal');
    }

}
