<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:43:46
 */

namespace Mg\Rh;

use Mg\MgModel;
use Mg\Rh\Indicador;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioProdutoBarra;

class IndicadorLancamento extends MgModel
{
    protected $table = 'tblindicadorlancamento';
    protected $primaryKey = 'codindicadorlancamento';


    protected $fillable = [
        'codindicador',
        'codnegocio',
        'codnegocioprodutobarra',
        'descricao',
        'estorno',
        'manual',
        'valor'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codindicador' => 'integer',
        'codindicadorlancamento' => 'integer',
        'codnegocio' => 'integer',
        'codnegocioprodutobarra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'estorno' => 'boolean',
        'manual' => 'boolean',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function Indicador()
    {
        return $this->belongsTo(Indicador::class, 'codindicador', 'codindicador');
    }

    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function NegocioProdutoBarra()
    {
        return $this->belongsTo(NegocioProdutoBarra::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }

}
