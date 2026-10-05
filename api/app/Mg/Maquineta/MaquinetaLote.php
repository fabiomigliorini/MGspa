<?php

namespace Mg\Maquineta;

use Mg\MgModel;
use Mg\Pagamento\Pagamento;
use Mg\Usuario\Usuario;

/**
 * O bordero da maquineta (M9 doc-3): o cartao cai no lote corrente (o mais
 * novo, se aberto); o gerente fecha digitando credito e debito do bordero.
 * creditosistema/debitosistema sao gravados no fechamento.
 */
class MaquinetaLote extends MgModel
{
    protected $table = 'tblmaquinetalote';
    protected $primaryKey = 'codmaquinetalote';

    protected $fillable = [
        'codmaquineta',
        'abertura',
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
    ];

    public function aberto(): bool
    {
        return empty($this->fechamento);
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
