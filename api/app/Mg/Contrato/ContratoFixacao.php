<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 23:47:38
 */

namespace Mg\Contrato;

use Mg\MgModel;
use Mg\Contrato\Contrato;
use Mg\Contrato\ContratoPagamento;
use Mg\Contrato\ContratoFixacaoCambio;
use Mg\Moeda\Moeda;

class ContratoFixacao extends MgModel
{
    protected $table = 'tblcontratofixacao';
    protected $primaryKey = 'codcontratofixacao';

    protected $fillable = [
        'codcontrato',
        'data',
        'datavencimento',
        'quantidade',
        'codmoeda',
        'preco',
        'observacao',
        'inativo',
        // Config fiscal congelada (base/alíquota/UPF/grupofethab; SEM valor).
        // Os valores em R$ (impostos/líquido) são derivados das travas de câmbio.
        'tributos',
        // 4 totais GRAVADOS, recalculados por ContratoFixacaoService::recalcular.
        'totalmoeda',
        'saldomoeda',
        'totalbrl',
        'liquidobrl',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcontrato' => 'integer',
        'codcontratofixacao' => 'integer',
        'codmoeda' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'data' => 'date',
        'datavencimento' => 'date',
        'inativo' => 'datetime',
        'preco' => 'float',
        'quantidade' => 'float',
        'totalmoeda' => 'float',
        'saldomoeda' => 'float',
        'totalbrl' => 'float',
        'liquidobrl' => 'float',
        'tributos' => 'array',
    ];

    // "É moeda estrangeira (≠ Real)?" — fonte única do predicado no backend.
    // Deriva do iso da moeda (FK codmoeda); lazy-load da relação quando preciso.
    public function getEstrangeiraAttribute(): bool
    {
        return ($this->Moeda->iso ?? 'BRL') !== 'BRL';
    }

    // Alias legado usado pelo fluxo de pagamento (a ser refatorado).
    public function getUsdAttribute(): bool
    {
        return $this->estrangeira;
    }

    // Chaves Estrangeiras
    public function Contrato()
    {
        return $this->belongsTo(Contrato::class, 'codcontrato', 'codcontrato');
    }

    public function Moeda()
    {
        return $this->belongsTo(Moeda::class, 'codmoeda', 'codmoeda');
    }

    // Travas de câmbio desta fixação (1 fixação : N travas). Base dos totais em
    // R$ (totalbrl/liquidobrl) e do saldo em moeda ainda a travar.
    public function ContratoFixacaoCambioS()
    {
        return $this->hasMany(ContratoFixacaoCambio::class, 'codcontratofixacao', 'codcontratofixacao');
    }

    // Parcelas de pagamento desta fixação (1 fixação : N parcelas).
    public function ContratoPagamentoS()
    {
        return $this->hasMany(ContratoPagamento::class, 'codcontratofixacao', 'codcontratofixacao');
    }
}
