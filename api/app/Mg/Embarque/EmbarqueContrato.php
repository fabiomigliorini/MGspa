<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 23:47:49
 */

namespace Mg\Embarque;

use Mg\MgModel;
use Mg\Contrato\Contrato;
use Mg\Embarque\Embarque;
use Mg\NotaFiscal\NotaFiscal;

class EmbarqueContrato extends MgModel
{
    protected $table = 'tblembarquecontrato';
    protected $primaryKey = 'codembarquecontrato';


    protected $fillable = [
        'chavenf',
        'codcontrato',
        'codembarque',
        'codnotafiscal',
        'numeronf',
        'quantidade',
        'valornf'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcontrato' => 'integer',
        'codembarque' => 'integer',
        'codembarquecontrato' => 'integer',
        'codnotafiscal' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'quantidade' => 'float',
        'valornf' => 'float'
    ];


    // Chaves Estrangeiras
    public function Contrato()
    {
        return $this->belongsTo(Contrato::class, 'codcontrato', 'codcontrato');
    }

    public function Embarque()
    {
        return $this->belongsTo(Embarque::class, 'codembarque', 'codembarque');
    }

    public function NotaFiscal()
    {
        return $this->belongsTo(NotaFiscal::class, 'codnotafiscal', 'codnotafiscal');
    }

}
