<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:41:35
 */

namespace Mg\Woo;

use Mg\MgModel;
use Mg\Woo\WooPedidoNegocio;
use Mg\Usuario\Usuario;

class WooPedido extends MgModel
{
    protected $table = 'tblwoopedido';
    protected $primaryKey = 'codwoopedido';


    protected $fillable = [
        'alteracaowoo',
        'cidade',
        'criacaowoo',
        'entrega',
        'id',
        'jsonwoo',
        'nome',
        'pagamento',
        'status',
        'valorfrete',
        'valortotal'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'alteracaowoo' => 'datetime',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codwoopedido' => 'integer',
        'criacao' => 'datetime',
        'criacaowoo' => 'datetime',
        'id' => 'integer',
        'valorfrete' => 'float',
        'valortotal' => 'float'
    ];


    // Chaves Estrangeiras
    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }


    // Tabelas Filhas
    public function WooPedidoNegocioS()
    {
        return $this->hasMany(WooPedidoNegocio::class, 'codwoopedido', 'codwoopedido');
    }

}
