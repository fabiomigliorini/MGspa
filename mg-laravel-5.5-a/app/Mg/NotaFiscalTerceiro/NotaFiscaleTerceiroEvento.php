<?php

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;

class NotaFiscaleTerceiroEvento extends MGModel
{
    protected $table = 'tblnotafiscalterceiroevento';
    protected $primaryKey = 'codnotafiscalterceiroevento';
    protected $fillable = [
        'coddistribuicaodfe',
        'nsu',
        'codorgao',
        'nfechave',
        'cnpj',
        'dhevento',
        'tpevento',
        'nseqevento',
        'evento',
        'dhrecebimento',
        'protocolo',
    ];
    protected $dates = [
        'dhenvento',
        'dhrecebimento',
        'criacao',
        'alteracao',
    ];

    // Chaves Estrangeiras
    public function NFeTerceiroDistribuicaoDfe()
    {
        return $this->belongsTo(NotaFiscalTerceiroDistribuicaoDfe::class, 'coddistribuicaodfe', 'nsu');
    }


    // Tabelas Filhas


}
