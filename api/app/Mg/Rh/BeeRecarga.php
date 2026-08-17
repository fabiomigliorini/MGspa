<?php
/**
 * BeeRecarga — LOTE de recarga do cartao-beneficio de UMA filial num periodo.
 *
 * Nasce no momento em que o RH exporta a planilha (por isso `exportacao` e'
 * NOT NULL: nao existe lote nao-exportado) e ja' carrega o titulo de
 * adiantamento a' Beevale que a geracao cria.
 *
 * O lote e' IMUTAVEL: a planilha de um lote antigo le' os itens gravados, nunca
 * recalcula o acerto. Inativar nao apaga item nenhum — os itens sao o historico
 * do que foi enviado a' operadora; eles apenas param de contar na previa, que
 * filtra por `inativo IS NULL` no lote pai.
 */

namespace Mg\Rh;

use Mg\Filial\Filial;
use Mg\MgModel;
use Mg\Titulo\Titulo;

class BeeRecarga extends MgModel
{
    protected $table = 'tblbeerecarga';
    protected $primaryKey = 'codbeerecarga';

    // Status do lote (coluna status)
    const STATUS_PENDENTE = 'PEND';  // planilha exportada, saldo ainda nao confirmado
    const STATUS_OK = 'OK';          // RH conferiu que o saldo caiu nos cartoes

    const STATUS_DESCRICAO = [
        self::STATUS_PENDENTE => 'Pendente',
        self::STATUS_OK => 'Confirmada',
    ];

    protected $fillable = [
        'codperiodo',
        'codfilial',
        'codtitulo',
        'valor',
        'dia',
        'status',
        'exportacao',
        'inativo',
        'observacao',
    ];

    protected $casts = [
        'codbeerecarga' => 'integer',
        'codperiodo' => 'integer',
        'codfilial' => 'integer',
        'codtitulo' => 'integer',
        'valor' => 'float',
        'dia' => 'date',
        'exportacao' => 'datetime',
        'inativo' => 'datetime',
        'criacao' => 'datetime',
        'alteracao' => 'datetime',
        'codusuariocriacao' => 'integer',
        'codusuarioalteracao' => 'integer',
    ];

    protected $appends = ['status_descricao'];

    public function getStatusDescricaoAttribute()
    {
        return self::STATUS_DESCRICAO[$this->status] ?? $this->status;
    }

    // Chaves Estrangeiras
    public function Periodo()
    {
        return $this->belongsTo(Periodo::class, 'codperiodo', 'codperiodo');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    // Adiantamento a' Beevale gerado junto com o lote
    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
    }

    public function BeeRecargaPeriodoColaboradorS()
    {
        return $this->hasMany(BeeRecargaPeriodoColaborador::class, 'codbeerecarga', 'codbeerecarga');
    }
}
