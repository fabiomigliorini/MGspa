<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Feb/2026 16:56:42
 */

namespace Mg\MetaUnidadeNegocioPessoaFixo;

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

    protected $dates = [
        'alteracao',
        'criacao',
        'datafinal',
        'datainicial'
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