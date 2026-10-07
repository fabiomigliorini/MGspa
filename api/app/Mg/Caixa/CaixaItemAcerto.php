<?php

namespace Mg\Caixa;

use Mg\MgModel;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;

/**
 * Debito ou ajuste na conta corrente da maquineta de parceiro (doc-4, "Itens
 * de parceiro"): T titulo a pagar gerado (valor negativo, com codtitulo) ou A
 * ajuste (com sinal e observacao: comissao que o parceiro desconta, saldo
 * inicial). valor = o efeito no que devemos ao parceiro. Cancela com
 * justificativa. Os creditos sao os borderos (tipo M no movimento do
 * portador).
 */
class CaixaItemAcerto extends MgModel
{
    protected $table = 'tblcaixaitemacerto';
    protected $primaryKey = 'codcaixaitemacerto';

    const TIPO_TITULO = 'T';
    const TIPO_AJUSTE = 'A';

    protected $fillable = [
        'codcaixaitem',
        'tipo',
        'valor',
        'codtitulo',
        'transacao',
        'observacoes',
        'cancelamento',
        'codusuariocancelamento',
        'justificativa',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'cancelamento' => 'datetime',
        'codcaixaitem' => 'integer',
        'codcaixaitemacerto' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocancelamento' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'transacao' => 'datetime',
        'valor' => 'float',
    ];

    // Chaves Estrangeiras
    public function CaixaItem()
    {
        return $this->belongsTo(CaixaItem::class, 'codcaixaitem', 'codcaixaitem');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
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
}
