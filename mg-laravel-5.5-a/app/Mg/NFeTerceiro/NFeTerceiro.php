<?php

namespace Mg\NFeTerceiro;

use Mg\MgModel;

class NFeTerceiro extends MGModel
{
    protected $table = 'tblnotafiscalterceiro';
    protected $primaryKey = 'codnotafiscalterceiro';
    protected $fillable = [
        'coddidtribuicaodfe',
        'codnotafiscal',
        'codnegocio',
        'codfilial',
        'codoperacao',
        'codnaturezaoperacao',
        'codpessoa',
        'emitente',
        'cnpj',
        'ie',
        'emissao',
        'ignorada',
        'indsituacao',
        'justificativa',
        'indmanifestacao',
        'nfechave',
        'modelo',
        'serie',
        'numero',
        'entrada',
        'valortotal',
        'icmsbase',
        'icmsvalor',
        'icmsstbase',
        'icmsstvalor',
        'ipivalor',
        'valorprodutos',
        'valorfrete',
        'valorseguro',
        'valordesconto',
        'valoroutras',
        'codusuariocriacao',
        'codusuarioalteracao'

    ];
    protected $dates = [
        'emissao',
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

    public function NaturezaOperacao()
    {
        return $this->belongsTo(NaturezaOperacao::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function NFeTerceiroDistribuicaoDfe()
    {
        return $this->belongsTo(NFeTerceiroDistribuicaoDfe::class, 'coddidtribuicaodfe', 'coddidtribuicaodfe');
    }

    // Tabelas Filhas
    public function NotaFiscalTerceiroGrupoS()
    {
        return $this->hasMany(NotaFiscalTerceiroGrupo::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

    public function NotaFiscalTerceiroDuplicataS()
    {
        return $this->hasMany(NotaFiscalTerceiroDuplicata::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

}
