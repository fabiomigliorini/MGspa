<?php

namespace Mg\NFeTerceiro;

use Mg\MgModel;

class NFeTerceiroDfe extends MGModel
{
    protected $table = 'tblnotafiscalterceirodfe';
    protected $primaryKey = 'codnotafiscalterceirodfe';
    protected $fillable = [
        'codfilial',
        'nfechave',
        'cnpj',
        'emitente',
        'ie',
        'emissao',
        'tipo',
        'valortotal',
        'digito',
        'recebimento',
        'protocolo',
        'csitnfe',
        'codusuariocriacao',
        'codusuarioalteracao'

    ];
    protected $dates = [
        'emissao',
        'recebimento',
        'criacao',
        'alteracao',
    ];

    // Chaves Estrangeiras
    // public function NotaFiscalTerceiro()
    // {
    //     return $this->belongsTo(NotaFiscalTerceiroNotafiscal::class, 'coddistribuicaodfe', 'coddistribuicaodfe');
    // }

    // Tabelas Filhas
    public function FilialS()
    {
        return $this->hasMany(Filial::class, 'codfilial', 'codfilial');
    }


}
