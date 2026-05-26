<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Jul/2021 11:29:03
 */

namespace Mg\Dfe;

use Mg\MgModel;
use Mg\Dfe\DfeTipo;
use Mg\Filial\Filial;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiro;
use Mg\Dfe\DistribuicaoDfeEvento;
use Mg\NfeTerceiro\NfeTerceiro;

class DistribuicaoDfe extends MgModel
{
    protected $table = 'tbldistribuicaodfe';
    protected $primaryKey = 'coddistribuicaodfe';


    protected $fillable = [
        'coddfetipo',
        'coddistribuicaodfeevento',
        'codfilial',
        'codnfeterceiro',
        'codnotafiscalterceiro',
        'data',
        'nfechave',
        'nsu'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'data'
    ];

    protected $casts = [
        'coddfetipo' => 'integer',
        'coddistribuicaodfe' => 'integer',
        'coddistribuicaodfeevento' => 'integer',
        'codfilial' => 'integer',
        'codnfeterceiro' => 'integer',
        'codnotafiscalterceiro' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function DfeTipo()
    {
        return $this->belongsTo(DfeTipo::class, 'coddfetipo', 'coddfetipo');
    }

    public function DistribuicaoDfeEvento()
    {
        return $this->belongsTo(DistribuicaoDfeEvento::class, 'coddistribuicaodfeevento', 'coddistribuicaodfeevento');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function NfeTerceiro()
    {
        return $this->belongsTo(NfeTerceiro::class, 'codnfeterceiro', 'codnfeterceiro');
    }

    public function NotaFiscalTerceiro()
    {
        return $this->belongsTo(NotaFiscalTerceiro::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

}