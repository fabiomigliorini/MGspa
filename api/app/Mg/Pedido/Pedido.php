<?php

namespace Mg\Pedido;

use App\Models\Usuario;
use Mg\Estoque\EstoqueLocal;
use Mg\GrupoEconomico\GrupoEconomico;
use Mg\MgModel;

class Pedido extends MgModel
{
    public const STATUS_PENDENTE = 10;
    public const STATUS_ATENDIDO = 20;
    public const STATUS_CANCELADO = 90;

    public const STATUS = [
        self::STATUS_PENDENTE => 'Pendente',
        self::STATUS_ATENDIDO => 'Atendido',
        self::STATUS_CANCELADO => 'Cancelado',
    ];

    public const TIPO_COMPRA = 10;
    public const TIPO_TRANSFERENCIA = 20;
    public const TIPO_VENDA = 90;

    public const TIPO = [
        self::TIPO_COMPRA => 'Compra',
        self::TIPO_TRANSFERENCIA => 'Transferência',
        self::TIPO_VENDA => 'Venda',
    ];

    protected $table = 'tblpedido';
    protected $primaryKey = 'codpedido';

    protected $fillable = [
        'codestoquelocal',
        'codestoquelocalorigem',
        'codgrupoeconomico',
        'indstatus',
        'indtipo',
        'observacoes',
    ];

    protected $casts = [
        'codestoquelocal' => 'integer',
        'codestoquelocalorigem' => 'integer',
        'codgrupoeconomico' => 'integer',
        'codpedido' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'indstatus' => 'integer',
        'indtipo' => 'integer',
        'alteracao' => 'datetime',
        'criacao' => 'datetime',
    ];

    public function EstoqueLocal()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocal', 'codestoquelocal');
    }

    public function EstoqueLocalOrigem()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocalorigem', 'codestoquelocal');
    }

    public function GrupoEconomico()
    {
        return $this->belongsTo(GrupoEconomico::class, 'codgrupoeconomico', 'codgrupoeconomico');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function PedidoItemS()
    {
        return $this->hasMany(PedidoItem::class, 'codpedido', 'codpedido');
    }
}
