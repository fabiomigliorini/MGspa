<?php

namespace Mg\NFeTerceiro;

use Mg\MgModel;

class NFeTerceiroDuplicata extends MGModel
{
    protected $table = 'tblnotafiscalterceiroduplicata';
    protected $primaryKey = 'codnotafiscalterceiroduplicata';
    protected $fillable = [
        'codnotafiscalterceiro',
        'codtitulo',
        'duplicata',
        'vencimento',
        'valor',
        'ndup',
        'dvenc',
        'vdup',
        'codusuariocriacao',
        'codusuarioalteracao'
        
    ];
    protected $dates = [
        'dvenc',
        'vencimento',
        'criacao',
        'alteracao'
    ];

    // Chaves Estrangeiras

    // Tabelas Filhas
    public function NotaFiscalTerceiroS()
    {
        return $this->hasMany(NotaFiscalTerceiro::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

}
