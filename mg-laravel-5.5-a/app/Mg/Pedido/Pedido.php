<?php

namespace Mg\Pedido;

use Mg\MgModel;

class Pedido extends MGModel
{
    const STATUS_PENDENTE         = 10;
    const STATUS_ATENDIDO         = 20;
    const STATUS_CANCELADO        = 90;

    const TIPO_COMPRA             = 10;
    const TIPO_TRANSFERENCIA      = 20;
    const TIPO_VENDA              = 90;

    protected $table = 'tblpedido';
    protected $primaryKey = 'codpedido';
    protected $fillable = [
        'indtipo',
        'observacoes',
        'codestoquelocal',
        'codestoquelocalorigem',
        'codgrupoeconomico'
    ];
    protected $dates = [
        'alteracao',
        'criacao'
    ];

    // Chaves Estrangeiras
    public function EstoqueLocal()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocal', 'codestoquelocalorigem');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    // Tabelas Filhas
    public function PedidoItemS()
    {
        return $this->hasMany(PedidoItem::class, 'codpedido', 'codpedido');
    }


}
