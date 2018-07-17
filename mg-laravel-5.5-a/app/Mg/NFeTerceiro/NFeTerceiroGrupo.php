<?php

namespace Mg\NFeTerceiro;

use Mg\MgModel;

class NFeTerceiroGrupo extends MGModel
{
    protected $table = 'tblnotafiscalterceirogrupo';
    protected $primaryKey = 'codnotafiscalterceirogrupo';
    protected $fillable = [
        'codnotafiscalterceiro',
        'codusuariocriacao',
        'codusuarioalteracao'
    ];
    protected $dates = [
        'criacao',
        'alteracao',
    ];

    // Chaves Estrangeiras
    public function NotaFiscalTerceiroItem()
    {
        return $this->belongsTo(NotaFiscalTerceiroItem::class, 'codnotafiscalterceirogrupo', 'codnotafiscalterceirogrupo');
    }

    public function NotaFiscalTerceiroProdutoBarra()
    {
        return $this->belongsTo(NotaFiscalTerceiroProdutoBarra::class, 'codnotafiscalterceirogrupo', 'codnotafiscalterceirogrupo');
    }

    // Tabelas Filhas
    public function NotaFiscalTerceiroS()
    {
        return $this->hasMany(NotaFiscalTerceiro::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

}
