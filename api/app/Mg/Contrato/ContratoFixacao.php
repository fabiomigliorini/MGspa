<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 23:47:38
 */

namespace Mg\Contrato;

use Mg\MgModel;
use Mg\Contrato\Contrato;

class ContratoFixacao extends MgModel
{
    protected $table = 'tblcontratofixacao';
    protected $primaryKey = 'codcontratofixacao';



    protected $fillable = [
        'codcontrato',
        'data',
        'dolar',
        'inativo',
        'isentofethab',
        'moeda',
        'observacao',
        'preco',
        'precoreal',
        'quantidade',
        // Snapshot dos impostos travado na fixação (modal de impostos)
        'precoliquido',
        'totaldeducao',
        'tributos'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcontrato' => 'integer',
        'codcontratofixacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'data' => 'date',
        'dolar' => 'float',
        'inativo' => 'datetime',
        'isentofethab' => 'boolean',
        'preco' => 'float',
        'precoreal' => 'float',
        'quantidade' => 'float',
        'precoliquido' => 'float',
        'totaldeducao' => 'float',
        'tributos' => 'array'
    ];


    // Chaves Estrangeiras
    public function Contrato()
    {
        return $this->belongsTo(Contrato::class, 'codcontrato', 'codcontrato');
    }

}
