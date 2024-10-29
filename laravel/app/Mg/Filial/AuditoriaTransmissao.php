<?php
/**
 * Created by php artisan gerador:model.
 * Date: 29/Oct/2024 12:28:06
 */

namespace Mg\Filial;

use Mg\MgModel;
use Mg\Filial\Auditoria;
use Mg\Filial\BaseRemota;

class AuditoriaTransmissao extends MgModel
{
    protected $table = 'tblauditoriatransmissao';
    protected $primaryKey = 'codauditoria';


    protected $fillable = [
        'codbaseremota',
        'data',
        'resultado',
        'transmitida'
    ];

    protected $dates = [
        'data'
    ];

    protected $casts = [
        'codauditoria' => 'integer',
        'codbaseremota' => 'integer',
        'transmitida' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function Auditoria()
    {
        return $this->belongsTo(Auditoria::class, 'codauditoria', 'codauditoria');
    }

    public function BaseRemota()
    {
        return $this->belongsTo(BaseRemota::class, 'codbaseremota', 'codbaseremota');
    }

}