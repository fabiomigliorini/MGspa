<?php

namespace Mg\NFeTerceiro;

use Mg\MgModel;

class NFeTerceiroItem extends MGModel
{
    protected $table = 'tblnotafiscalterceiroitem';
    protected $primaryKey = 'codnotafiscalterceiroitem';
    protected $fillable = [
        'codnotafiscalterceirogrupo',
        'numero',
        'referencia',
        'produto',
        'ncm',
        'cfop',
        'barrastributavel',
        'unidademedidatributavel',
        'quantidadetributavel',
        'valorunitariotributavel',
        'barras',
        'unidademedida',
        'quantidade',
        'valorunitario',
        'valorproduto',
        'valorfrete',
        'valorseguro',
        'valordesconto',
        'valoroutras',
        'valortotal',
        'compoetotal',
        'csosn',
        'origem',
        'icmsbasemodalidade',
        'icmsbase',
        'icmspercentual',
        'icmsvalor',
        'icmscst',
        'icmsstbasemodalidade',
        'icmsstbase',
        'icmsstpercentual',
        'icmsstvalor',
        'ipicst',
        'ipibase',
        'ipipercentual',
        'ipivalor',
        'piscst',
        'pisbase',
        'pispercentual',
        'pisvalor',
        'cofinscst',
        'cofinsbase',
        'cofinspercentual',
        'cofinsvalor'  

    ];
    protected $dates = [
        'criacao',
        'codusuariocriacao',
        'alteracao',
        'codusuarioalteracao'
    ];

    // Chaves Estrangeiras

    // Tabelas Filhas
    public function NotaFiscalTerceiroGrupoS()
    {
        return $this->hasMany(NotaFiscalTerceiroGrupo::class, 'codnotafiscalterceirogrupo', 'codnotafiscalterceirogrupo');
    }

}
