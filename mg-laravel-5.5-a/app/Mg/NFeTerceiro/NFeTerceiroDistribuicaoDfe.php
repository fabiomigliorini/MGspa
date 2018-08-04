<?php

namespace Mg\NFeTerceiro;

use Mg\MgModel;

class NFeTerceiroDistribuicaoDfe extends MGModel
{
    protected $table = 'tbldistribuicaodfe';
    protected $primaryKey = 'coddistribuicaodfe';
    protected $fillable = [
        'codfilial',
        'nsu',
        'schema',
        'codusuariocriacao',
        'codusuarioalteracao'

    ];
    protected $dates = [
        'criacao',
        'alteracao'
    ];

    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuarioalteracao');
    }

    // Tabelas Filhas
    public function NotaFiscalTerceiroS()
    {
        return $this->hasMany(NotaFiscalTerceiroNotafiscal::class, 'coddistribuicaodfe', 'coddistribuicaodfe');
    }


}
