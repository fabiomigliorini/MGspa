<?php

namespace Mg\NFeTerceiro;

use Mg\MgModel;

class NotaFiscalTerceiroDistribuicao extends MGModel
{
    protected $table = 'tblnotafiscalterceiroduplicata';
    protected $primaryKey = 'codnotafiscalterceiroduplicata';
    protected $fillable = [
        'codnotafiscalterceiro',
        'codtitulo',
        'duplicata',
        'vencimento',
        'valor'
    ];
    protected $dates = [
        'vencimento',
        'criacao',
        'codusuariocriacao',
        'alteracao',
        'codusuarioalteracao'
    ];

    // Chaves Estrangeiras

    // Tabelas Filhas
    public function NotaFiscalTerceiroS()
    {
        return $this->hasMany(NotaFiscalTerceiro::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

}
