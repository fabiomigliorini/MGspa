<?php

namespace Mg\NFeTerceiro;

use Mg\MgModel;

class NFeTerceiroProdutoBarra extends MGModel
{
    protected $table = 'tblnotafiscalterceiroprodutobarra';
    protected $primaryKey = 'codnotafiscalterceiroprodutobarra';
    protected $fillable = [
        'codnotafiscalterceirogrupo',
        'codprodutobarra',
        'margem',
        'complemento',
        'quantidade',
        'valorproduto',
        'codusuariocriacao',
        'codusuarioalteracao'
    ];
    protected $dates = [
        'criacao',
        'alteracao'
    ];

    // Chaves Estrangeiras
    public function NotaFiscalTerceiroGrupo()
    {
        return $this->belongsTo(NFeTerceiroGrupo::class, 'codnotafiscalterceirogrupo', 'codnotafiscalterceirogrupo');
    }

    // Tabelas Filhas
    public function ProdutoBarraS()
    {
        return $this->hasMany(ProdutoBarra::class, 'codprodutobarra', 'codprodutobarra');
    }



}
