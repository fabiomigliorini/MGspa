<?php

namespace Mg\NFeTerceiro;

use Mg\MgModel;

class NotaFiscalTerceiroGrupo extends MGModel
{
    protected $table = 'tblnotafiscalterceirogrupo';
    protected $primaryKey = 'codnotafiscalterceirogrupo';
    protected $fillable = [
        'codnotafiscalterceiro'
    ];
    protected $dates = [
        'criacao',
        'codusuariocriacao',
        'alteracao',
        'codusuarioalteracao',
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
