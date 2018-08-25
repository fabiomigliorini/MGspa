<?php

namespace Mg\Pedido;

use Mg\MgModel;

const PEDIDO_PENDENTE         = 10;
const PEDIDO_ATENDIDO         = 20;
const PEDIDO_CANCELADO        = 90;


class Pedido extends MGModel
{
    protected $table = 'tblpedido';
    protected $primaryKey = 'codpedido';
    protected $fillable = [
        'indtipo',
        'indstatus',
        'observacoes',
        'codestoquelocal',
        'codestoquelocalorigem'
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
