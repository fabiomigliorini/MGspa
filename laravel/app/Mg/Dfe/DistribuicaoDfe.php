<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Jul/2020 15:21:43
 */

namespace Mg\Dfe;

use Mg\MgModel;
use Mg\Dfe\DistribuicaoDfeEvento;
use Mg\Dfe\DfeTipo;
use Mg\Filial\Filial;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiro;

class DistribuicaoDfe extends MgModel
{
    protected $table = 'tbldistribuicaodfe';
    protected $primaryKey = 'coddistribuicaodfe';


    protected $fillable = [
        'coddfetipo',
        'codfilial',
        'codnotafiscalterceiro',
        'nsu'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'coddfetipo' => 'integer',
        'coddistribuicaodfe' => 'integer',
        'codfilial' => 'integer',
        'codnotafiscalterceiro' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function DfeTipo()
    {
        return $this->belongsTo(DfeTipo::class, 'coddfetipo', 'coddfetipo');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function NotaFiscalTerceiro()
    {
        return $this->belongsTo(NotaFiscalTerceiro::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }


    // Tabelas Filhas
    public function DistribuicaoDfeEventoS()
    {
        return $this->hasMany(DistribuicaoDfeEvento::class, 'coddistribuicaodfe', 'coddistribuicaodfe');
    }

}