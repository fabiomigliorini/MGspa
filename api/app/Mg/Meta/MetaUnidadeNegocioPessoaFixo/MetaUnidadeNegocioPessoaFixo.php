<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 12:07:01
 */

namespace Mg\Meta\MetaUnidadeNegocioPessoaFixo;

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
        'datafinal',
        'datainicial',
        'descricao',
        'quantidade',
        'tipo',
        'valor'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codmeta' => 'integer',
        'codmetaunidadenegociopessoafixo' => 'integer',
        'codpessoa' => 'integer',
        'codunidadenegocio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'datafinal' => 'date',
        'datainicial' => 'date',
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
