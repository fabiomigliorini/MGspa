<?php

namespace Mg\Portador;

use Mg\Caixa\CaixaItemLancamento;
use Mg\MgModel;
use Mg\Pagamento\Pagamento;
use Mg\Usuario\Usuario;

/**
 * Periodo do portador (M9 doc-3; modelo do M10). No caixa (especie) e' a
 * sessao: o intervalo (inicio/fim; fim nulo = aberta, recebe movimento), o
 * fechamento (quando e quem; fechado, o razao trava) e a contagem inicial e
 * final ({face: quantidade}) ao lado dos saldos.
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
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codportador' => 'integer',
        'codportadorperiodo' => 'integer',
        'contageminicial' => 'array',
        'contagemfinal' => 'array',
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
    ];

    public function aberto(): bool
    {
        return empty($this->fim);
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
    public function CaixaItemLancamentoS()
    {
        return $this->hasMany(CaixaItemLancamento::class, 'codportadorperiodo', 'codportadorperiodo');
    }

    public function PagamentoS()
    {
        return $this->hasMany(Pagamento::class, 'codportadorperiodo', 'codportadorperiodo');
    }
}
