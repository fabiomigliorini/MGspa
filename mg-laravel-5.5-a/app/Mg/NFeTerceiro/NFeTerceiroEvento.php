<?php

namespace Mg\NFeTerceiro;

use Mg\MgModel;

class NFeTerceiroEvento extends MGModel
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
        return $this->belongsTo(NFeTerceiroDistribuicaoDfe::class, 'coddistribuicaodfe', 'nsu');
    }


    // Tabelas Filhas


}
