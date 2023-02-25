<?php
/**
 * Created by php artisan gerador:model.
 * Date: 25/Feb/2023 12:41:16
 */

namespace Mg\Pedido;

use Mg\MgModel;
use Mg\Pedido\PedidoItem;
use Mg\Estoque\EstoqueLocal;
use Mg\GrupoEconomico\GrupoEconomico;
use Mg\Usuario\Usuario;

class Pedido extends MgModel
{
    const STATUS_PENDENTE         = 10;
    const STATUS_ATENDIDO         = 20;
    const STATUS_CANCELADO        = 90;

    const STATUS = [
      self::STATUS_PENDENTE => 'Pendente',
      self::STATUS_ATENDIDO => 'Atendido',
      self::STATUS_CANCELADO => 'Cancelado',
    ];

    const TIPO_COMPRA             = 10;
    const TIPO_TRANSFERENCIA      = 20;
    const TIPO_VENDA              = 90;

    const TIPO = [
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
        'observacoes'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codestoquelocal' => 'integer',
        'codestoquelocalorigem' => 'integer',
        'codgrupoeconomico' => 'integer',
        'codpedido' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'indstatus' => 'integer',
        'indtipo' => 'integer'
    ];


    // Chaves Estrangeiras
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


    // Tabelas Filhas
    public function PedidoItemS()
    {
        return $this->hasMany(PedidoItem::class, 'codpedido', 'codpedido');
    }

}