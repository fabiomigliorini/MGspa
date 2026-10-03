<?php

namespace Mg\Portador;

use Mg\Caixa\CaixaItemLancamento;
use Mg\MgModel;
use Mg\Pagamento\Pagamento;
use Mg\Usuario\Usuario;

/**
 * Periodo do portador (M9 doc-3; modelo do M10). Na gaveta e' a sessao do
 * caixa: abre e fecha com contagem no PDV (fim/fechamento + saldofinal =
 * contado; desde o M13 fechar grava tambem a conferencia e o razao trava).
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
        'moedasabertura',
        'cedulasabertura',
        'moedasfechamento',
        'cedulasfechamento',
        'conferencia',
        'codusuarioconferencia',
        'valorconferido',
        'observacoes',
        'codpagamentoabertura',
        'codpagamentofechamento',
        'contagemabertura',
        'contagemfechamento',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'cedulasabertura' => 'float',
        'cedulasfechamento' => 'float',
        'codportador' => 'integer',
        'codportadorperiodo' => 'integer',
        'codpagamentoabertura' => 'integer',
        'codpagamentofechamento' => 'integer',
        'contagemabertura' => 'array',
        'contagemfechamento' => 'array',
        'codusuarioabertura' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuarioconferencia' => 'integer',
        'codusuariocriacao' => 'integer',
        'codusuariofechamento' => 'integer',
        'conferencia' => 'datetime',
        'criacao' => 'datetime',
        'fechamento' => 'datetime',
        'fim' => 'datetime',
        'inicio' => 'datetime',
        'moedasabertura' => 'float',
        'moedasfechamento' => 'float',
        'saldofinal' => 'float',
        'saldoinicial' => 'float',
        'valorconferido' => 'float',
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

    public function UsuarioConferencia()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioconferencia', 'codusuario');
    }

    public function PagamentoAbertura()
    {
        return $this->belongsTo(Pagamento::class, 'codpagamentoabertura', 'codpagamento');
    }

    public function PagamentoFechamento()
    {
        return $this->belongsTo(Pagamento::class, 'codpagamentofechamento', 'codpagamento');
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
