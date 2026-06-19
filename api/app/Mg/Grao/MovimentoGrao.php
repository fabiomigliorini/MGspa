<?php

namespace Mg\Grao;

use Mg\MgModel;
use Mg\Safra\Safra;
use Mg\Fazenda\Plantio;
use Mg\Contrato\Contrato;

/**
 * Movimento de grao = linha do extrato/razao (estilo extrato bancario).
 *
 *  - Automatico: gerado a partir dos pontos da carga (codcarga, manual=false).
 *    Idempotente — recalc regera (apaga/upsert) so essas linhas.
 *  - Manual: ajuste comercial lancado pelo usuario (codcarga null, manual=true).
 *    O recalc NUNCA toca nele.
 *
 * `liquido` COM SINAL e o saldo da conta: UNIDADE +entrada(destino)/-saida(origem);
 * PLANTIO (producao colhida) e CONTRATO (entregue/recebido) sao contadores (+).
 * saldo(conta) = SUM(liquido). Invariante: liquido = bruto - desconto.
 */
class MovimentoGrao extends MgModel
{
    protected $table = 'tblmovimentograo';
    protected $primaryKey = 'codmovimentograo';

    protected $fillable = [
        'codcarga',
        'manual',
        'codsafra',
        'data',
        'papel',
        'contatipo',
        'codplantio',
        'codunidadearmazenadora',
        'codcontrato',
        'bruto',
        'desconto',
        'liquido',
        'observacao',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'bruto' => 'float',
        'codcarga' => 'integer',
        'codcontrato' => 'integer',
        'codmovimentograo' => 'integer',
        'codplantio' => 'integer',
        'codsafra' => 'integer',
        'codunidadearmazenadora' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'data' => 'datetime',
        'desconto' => 'float',
        'inativo' => 'datetime',
        'liquido' => 'float',
        'manual' => 'boolean',
    ];

    // Chaves Estrangeiras
    public function Carga()
    {
        return $this->belongsTo(Carga::class, 'codcarga', 'codcarga');
    }

    public function Safra()
    {
        return $this->belongsTo(Safra::class, 'codsafra', 'codsafra');
    }

    public function Plantio()
    {
        return $this->belongsTo(Plantio::class, 'codplantio', 'codplantio');
    }

    public function UnidadeArmazenadora()
    {
        return $this->belongsTo(UnidadeArmazenadora::class, 'codunidadearmazenadora', 'codunidadearmazenadora');
    }

    public function Contrato()
    {
        return $this->belongsTo(Contrato::class, 'codcontrato', 'codcontrato');
    }
}
