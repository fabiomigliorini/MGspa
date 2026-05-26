<?php

namespace Mg\Meta;

use Mg\MgModel;
use Mg\Meta\Meta;
use Mg\Pessoa\Pessoa;
use Mg\Filial\UnidadeNegocio;

class MetaUnidadeNegocioPessoaFixo extends MgModel
{
    protected $table = 'tblmetaunidadenegociopessoafixo';
    protected $primaryKey = 'codmetaunidadenegociopessoafixo';


    protected $fillable = [
        'codmeta',
        'codpessoa',
        'codunidadenegocio',
        'datainicial',
        'datafinal',
        'descricao',
        'quantidade',
        'tipo',
        'valor'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'datainicial',
        'datafinal'
    ];

    protected $casts = [
        'codmeta' => 'integer',
        'codmetaunidadenegociopessoafixo' => 'integer',
        'codpessoa' => 'integer',
        'codunidadenegocio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'quantidade' => 'float',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function Meta()
    {
        return $this->belongsTo(Meta::class, 'codmeta', 'codmeta');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function UnidadeNegocio()
    {
        return $this->belongsTo(UnidadeNegocio::class, 'codunidadenegocio', 'codunidadenegocio');
    }

}
