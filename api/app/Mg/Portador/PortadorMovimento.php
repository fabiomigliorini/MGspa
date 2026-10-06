<?php

namespace Mg\Portador;

use Mg\Caixa\CaixaItem;
use Mg\MgModel;
use Mg\Pagamento\Pagamento;
use Mg\Usuario\Usuario;

/**
 * Movimento do portador (doc-4, redefinicao do dinheiro): o registro
 * principal do saldo. Tipo P pagamento (nasce do pagamento,
 * PortadorMovimentoService::sincronizar; `inativo` = linha trocada), A ajuste
 * e T transferencia (PortadorLancamentoService; a transferencia sao duas
 * linhas ligadas pelo par, com o mesmo estado), I item do caixa (a entrada ou
 * saida do item no portador em especie: codcaixaitem e as linhas em `itens`; o
 * item conta como cedula, vender nao lanca nada). valor com sinal (positivo
 * entrou); transacao = quando aparece no portador. Mantido a mao.
 */
class PortadorMovimento extends MgModel
{
    protected $table = 'tblportadormovimento';
    protected $primaryKey = 'codportadormovimento';

    const TIPO_PAGAMENTO = 'P';
    const TIPO_AJUSTE = 'A';
    const TIPO_TRANSFERENCIA = 'T';
    const TIPO_ITEM = 'I';

    // so' ajuste, transferencia e item
    const ESTADO_PENDENTE = 'P';
    const ESTADO_EFETIVADO = 'E';
    const ESTADO_CANCELADO = 'C';

    protected $fillable = [
        'codportador',
        'codportadorperiodo',
        'codpagamento',
        'valor',
        'transacao',
        'parcela',
        'conciliado',
        'inativo',
        'tipo',
        'estado',
        'observacoes',
        'codportadormovimentopar',
        'confirmacao',
        'codusuarioconfirmacao',
        'cancelamento',
        'codusuariocancelamento',
        'justificativa',
        'codcaixaitem',
        'itens',
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
        'codportadormovimentopar' => 'integer',
        'confirmacao' => 'datetime',
        'codusuarioconfirmacao' => 'integer',
        'cancelamento' => 'datetime',
        'codusuariocancelamento' => 'integer',
        'codcaixaitem' => 'integer',
        'itens' => 'array',
    ];

    // conta no saldo: a linha do pagamento nao trocada; ajuste e
    // transferencia nao cancelados (a confirmar conta, decisao 13)
    public function valendo(): bool
    {
        return empty($this->inativo) && $this->estado != self::ESTADO_CANCELADO;
    }

    // Chaves Estrangeiras
    public function CaixaItem()
    {
        return $this->belongsTo(CaixaItem::class, 'codcaixaitem', 'codcaixaitem');
    }

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

    public function Par()
    {
        return $this->belongsTo(PortadorMovimento::class, 'codportadormovimentopar', 'codportadormovimento');
    }

    public function UsuarioConfirmacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioconfirmacao', 'codusuario');
    }

    public function UsuarioCancelamento()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocancelamento', 'codusuario');
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
