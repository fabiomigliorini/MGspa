<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:42:36
 */

namespace Mg\Meta;

use Mg\MgModel;
use Mg\Meta\Meta;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\Pessoa\Pessoa;
use Mg\Filial\UnidadeNegocio;

class BonificacaoEvento extends MgModel
{
    protected $table = 'tblbonificacaoevento';
    protected $primaryKey = 'codbonificacaoevento';


    protected $fillable = [
        'codmeta',
        'codnegocio',
        'codnegocioprodutobarra',
        'codpessoa',
        'codunidadenegocio',
        'descricao',
        'lancamento',
        'manual',
        'tipo',
        'valor'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codbonificacaoevento' => 'integer',
        'codmeta' => 'integer',
        'codnegocio' => 'integer',
        'codnegocioprodutobarra' => 'integer',
        'codpessoa' => 'integer',
        'codunidadenegocio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'lancamento' => 'datetime',
        'manual' => 'boolean',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function Meta()
    {
        return $this->belongsTo(Meta::class, 'codmeta', 'codmeta');
    }

    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function NegocioProdutoBarra()
    {
        return $this->belongsTo(NegocioProdutoBarra::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
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
