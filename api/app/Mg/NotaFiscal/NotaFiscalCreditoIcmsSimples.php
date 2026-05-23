<?php
/**
 * Created by php artisan gerador:model.
 * Date: 26/Jan/2021 08:51:59
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

    protected $dates = [
        
    ];

    protected $casts = [
        'codnotafiscal' => 'integer',
        'codnotafiscalcreditoicmssimples' => 'integer',
        'percentual' => 'float'
    ];


    // Chaves Estrangeiras
    public function NotaFiscal()
    {
        return $this->belongsTo(NotaFiscal::class, 'codnotafiscal', 'codnotafiscal');
    }

}