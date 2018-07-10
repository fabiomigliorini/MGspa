<?php

namespace Mg\NFeTerceiro;

use Mg\MgModel;

class NotaFiscalTerceiroDistribuicaoDfe extends MGModel
{
    protected $table = 'tbldistribuicaodfe';
    protected $primaryKey = 'coddistribuicaodfe';
    protected $fillable = [
        'codfilial',
        'nsu',
        'schema'
    ];
    protected $dates = [
        'criacao',
        'codusuariocriacao',
        'alteracao',
        'codusuarioalteracao'
    ];

    // Chaves Estrangeiras
    public function NotaFiscalTerceiro()
    {
        return $this->belongsTo(NotaFiscalTerceiroNotafiscal::class, 'coddistribuicaodfe', 'coddistribuicaodfe');
    }

    // Tabelas Filhas
    public function FilialS()
    {
        return $this->hasMany(Filial::class, 'codfilial', 'codfilial');
    }


}
