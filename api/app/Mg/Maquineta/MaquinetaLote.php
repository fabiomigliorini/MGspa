<?php

namespace Mg\Maquineta;

use Mg\MgModel;
use Mg\Pagamento\Pagamento;
use Mg\Usuario\Usuario;

/**
 * O periodo da maquineta (TASK-188 M9.8; no banco, lote): o cartao cai no
 * periodo aberto (sem fim). O gerente digita credito e debito do bordero:
 * o periodo ganha fim (abre o seguinte) e fica conferido se bater no
 * centavo, senao pendente. creditosistema/debitosistema sao o sistema no
 * momento da ultima conferencia.
 */
class MaquinetaLote extends MgModel
{
    protected $table = 'tblmaquinetalote';
    protected $primaryKey = 'codmaquinetalote';

    protected $fillable = [
        'codmaquineta',
        'abertura',
        'fim',
        'fechamento',
        'codusuariofechamento',
        'creditoinformado',
        'debitoinformado',
        'creditosistema',
        'debitosistema',
        'observacoes',
    ];

    protected $casts = [
        'abertura' => 'datetime',
        'alteracao' => 'datetime',
        'codmaquineta' => 'integer',
        'codmaquinetalote' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codusuariofechamento' => 'integer',
        'creditoinformado' => 'float',
        'creditosistema' => 'float',
        'criacao' => 'datetime',
        'debitoinformado' => 'float',
        'debitosistema' => 'float',
        'fechamento' => 'datetime',
        'fim' => 'datetime',
    ];

    const ABERTO = 'aberto';
    const PENDENTE = 'pendente';
    const CONFERIDO = 'conferido';

    // aberto = recebe o cartao (sem fim)
    public function aberto(): bool
    {
        return empty($this->fim);
    }

    public function conferido(): bool
    {
        return !empty($this->fechamento);
    }

    public function situacao(): string
    {
        if ($this->conferido()) {
            return static::CONFERIDO;
        }
        return $this->aberto() ? static::ABERTO : static::PENDENTE;
    }

    // Chaves Estrangeiras
    public function Maquineta()
    {
        return $this->belongsTo(Maquineta::class, 'codmaquineta', 'codmaquineta');
    }

    public function UsuarioFechamento()
    {
        return $this->belongsTo(Usuario::class, 'codusuariofechamento', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    // Tabelas Filhas
    public function PagamentoS()
    {
        return $this->hasMany(Pagamento::class, 'codmaquinetalote', 'codmaquinetalote');
    }

    public function PagamentoCanceladoS()
    {
        return $this->hasMany(Pagamento::class, 'codmaquinetalotecancelamento', 'codmaquinetalote');
    }
}
