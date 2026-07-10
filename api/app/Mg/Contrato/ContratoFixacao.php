<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 23:47:38
 */

namespace Mg\Contrato;

use Mg\MgModel;
use Mg\Contrato\Contrato;
use Mg\Contrato\ContratoPagamento;

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


    // "É moeda estrangeira (US$)?" — fonte única do predicado no backend
    // (Resource/Requests/Controller usam $fixacao->usd em vez de reimplementar).
    public function getUsdAttribute(): bool
    {
        return ($this->moeda ?: 'BRL') !== 'BRL';
    }

    // Chaves Estrangeiras
    public function Contrato()
    {
        return $this->belongsTo(Contrato::class, 'codcontrato', 'codcontrato');
    }

    // Parcelas de pagamento desta fixação (1 fixação : N parcelas). Base do
    // ledger por fixação (sacas parceladas/recebidas, recebido R$, saldo).
    public function ContratoPagamentoS()
    {
        return $this->hasMany(ContratoPagamento::class, 'codcontratofixacao', 'codcontratofixacao');
    }

}
