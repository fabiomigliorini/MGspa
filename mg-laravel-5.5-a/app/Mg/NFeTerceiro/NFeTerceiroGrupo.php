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
    public function NotaFiscalTerceiro()
    {
        return $this->belongsTo(NotaFiscalTerceiro::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

    // Tabelas Filhas
    public function NotaFiscalTerceiroItemS()
    {
        return $this->hasMany(NotaFiscalTerceiroItem::class, 'codnotafiscalterceirogrupo', 'codnotafiscalterceirogrupo');
    }

    public function NotaFiscalTerceiroProdutoBarraS()
    {
        return $this->hasMany(NotaFiscalTerceiroProdutoBarra::class, 'codnotafiscalterceirogrupo', 'codnotafiscalterceirogrupo');
    }


}
