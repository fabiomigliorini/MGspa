<?php

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;

class NotaFiscalTerceiroItem extends MGModel
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
        'cofinsvalor',
        'conferido'

    ];
    protected $dates = [
        'criacao',
        'alteracao',
    ];


    // Chaves Estrangeiras
    public function NotaFiscalTerceiroGrupo()
    {
        return $this->belongsTo(NotaFiscalTerceiroGrupo::class, 'codnotafiscalterceirogrupo', 'codnotafiscalterceirogrupo');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    // Tabelas Filhas
}
