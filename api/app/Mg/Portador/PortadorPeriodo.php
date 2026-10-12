<?php

namespace Mg\Portador;

use Mg\MgModel;
use Mg\Pagamento\Pagamento;
use Mg\Usuario\Usuario;

/**
 * Periodo do portador (doc-4, redefinicao do dinheiro). Tres estados: aberto
 * (sem fim, recebe o movimento do dia a dia), pendente (com fim, sem
 * fechamento: so' correcao) e fechado (o razao trava). Na especie, o saldo
 * inicial e' a contagem final do anterior; a contagem inicial so' confere e a
 * final da' a diferenca (contagem final - saldo final). A contagem tem tambem
 * os itens do caixa (contagemitens*), que contam como cedula.
 */
class PortadorPeriodo extends MgModel
{
    protected $table = 'tblportadorperiodo';
    protected $primaryKey = 'codportadorperiodo';

    protected $fillable = [
        'codportador',
        'inicio',
        'fim',
        'fechamento',
        'codusuarioabertura',
        'codusuariofechamento',
        'saldoinicial',
        'saldofinal',
        'observacoes',
        'contageminicial',
        'contagemfinal',
        'contagemitensinicial',
        'contagemitensfinal',
        'diferenca',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codportador' => 'integer',
        'codportadorperiodo' => 'integer',
        'contageminicial' => 'array',
        'contagemfinal' => 'array',
        'contagemitensinicial' => 'array',
        'contagemitensfinal' => 'array',
        'codusuarioabertura' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codusuariofechamento' => 'integer',
        'criacao' => 'datetime',
        'fechamento' => 'datetime',
        'fim' => 'datetime',
        'inicio' => 'datetime',
        'saldofinal' => 'float',
        'saldoinicial' => 'float',
        'diferenca' => 'float',
    ];

    public function aberto(): bool
    {
        return empty($this->fim);
    }

    public function fechado(): bool
    {
        return !empty($this->fechamento);
    }

    public function pendente(): bool
    {
        return !empty($this->fim) && empty($this->fechamento);
    }

    // Chaves Estrangeiras
    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function UsuarioAbertura()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioabertura', 'codusuario');
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
        return $this->hasMany(Pagamento::class, 'codportadorperiodo', 'codportadorperiodo');
    }
}
