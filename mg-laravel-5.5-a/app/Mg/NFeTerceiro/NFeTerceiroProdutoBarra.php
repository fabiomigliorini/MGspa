<?php

namespace Mg\NFeTerceiro;

use Mg\MgModel;

class NotaFiscalTerceiroProdutoBarra extends MGModel
{
    protected $table = 'tblnotafiscalterceiroprodutobarra';
    protected $primaryKey = 'codnotafiscalterceiroprodutobarra';
    protected $fillable = [
        'codnotafiscalterceirogrupo',
        'codprodutobarra',
        'margem',
        'complemento',
        'quantidade',
        'valorproduto'
    ];
    protected $dates = [
        'criacao',
        'codusuariocriacao',
        'alteracao',
        'codusuarioalteracao'
    ];

    // Chaves Estrangeiras

    // Tabelas Filhas
    public function ProdutoBarraS()
    {
        return $this->hasMany(ProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }

    public function NotaFiscalTerceiroGrupoS()
    {
        return $this->hasMany(NotaFiscalTerceiroGrupo::class, 'codnotafiscalterceirogrupo', 'codnotafiscalterceirogrupo');
    }

}
