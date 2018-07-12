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
        'valoroutras'
    ];
    protected $dates = [
        'emissao',
        'criacao',
        'codusuariocriacao',
        'alteracao',
        'codusuarioalteracao'
    ];

    // Chaves Estrangeiras
    public function NotaFiscalTerceiroDuplicata()
    {
        return $this->belongsTo(NotaFiscalTerceiroDuplicata::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

    public function NotaFiscalTerceiroGrupo()
    {
        return $this->belongsTo(NotaFiscalTerceiroGrupo::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

    // Tabelas Filhas
    public function NotaFiscalTerceiroDistribuicaoDfeS()
    {
        return $this->hasMany(NotaFiscalTerceiroDistribuicaoDfe::class, 'coddidtribuicaodfe', 'coddidtribuicaodfe');
    }

    public function FilialS()
    {
        return $this->hasMany(Filial::class, 'codfilial', 'codfilial');
    }

    public function PessoaS()
    {
        return $this->hasMany(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function NaturezaOperacaoS()
    {
        return $this->hasMany(NaturezaOperacao::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }


}
