<?php

namespace Mg\NFeTerceiro;

use Mg\MgModel;

class NFeTerceiroDfe extends MGModel
{
    protected $table = 'tblnotafiscalterceirodfe';
    protected $primaryKey = 'codnotafiscalterceirodfe';
    protected $fillable = [
        'codfilial',
        'codpessoa',
        'nfechave',
        'cnpj',
        'emitente',
        'ie',
        'emissao',
        'tipo',
        'valortotal',
        'digito',
        'recebimento',
        'protocolo',
        'csitnfe',
        'download',
        'indmanifestacao',
        'justificativa',
        'codusuariocriacao',
        'codusuarioalteracao'

    ];
    protected $dates = [
        'emissao',
        'recebimento',
        'criacao',
        'alteracao',
    ];

    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuarioalteracao');
    }

    // Tabelas Filhas


}
