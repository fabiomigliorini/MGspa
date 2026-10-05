<?php

namespace Mg\Portador;

use Mg\MgModel;
use Mg\Pagamento\Pagamento;
use Mg\Usuario\Usuario;

/**
 * Razao do dinheiro (M10 doc-3): o que cai em cada portador e quando. Toda
 * linha nasce de um pagamento (PortadorMovimentoService::sincronizar).
 * valor com sinal (positivo entrou); transacao = quando aparece no portador.
 * Mantido a mao (fora do gerador de models).
 */
class PortadorMovimento extends MgModel
{
    protected $table = 'tblportadormovimento';
    protected $primaryKey = 'codportadormovimento';

    protected $fillable = [
        'codportador',
        'codportadorperiodo',
        'codpagamento',
        'valor',
        'transacao',
        'parcela',
        'conciliado',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codpagamento' => 'integer',
        'codportador' => 'integer',
        'codportadormovimento' => 'integer',
        'codportadorperiodo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'conciliado' => 'boolean',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'parcela' => 'integer',
        'transacao' => 'datetime',
        'valor' => 'float',
    ];

    // Chaves Estrangeiras
    public function Pagamento()
    {
        return $this->belongsTo(Pagamento::class, 'codpagamento', 'codpagamento');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
    }

    public function PortadorPeriodo()
    {
        return $this->belongsTo(PortadorPeriodo::class, 'codportadorperiodo', 'codportadorperiodo');
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
    public function ExtratoBancarioPortadorMovimentoS()
    {
        return $this->hasMany(ExtratoBancarioPortadorMovimento::class, 'codportadormovimento', 'codportadormovimento');
    }
}
