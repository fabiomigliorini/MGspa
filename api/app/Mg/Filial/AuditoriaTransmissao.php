<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:40:30
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codauditoria' => 'integer',
        'codbaseremota' => 'integer',
        'criacao' => 'datetime',
        'data' => 'datetime',
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
