<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Dec/2025 11:00:37
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

    protected $dates = [
        'alteracao',
        'alteracaowoo',
        'criacao',
        'criacaowoo'
    ];

    protected $casts = [
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codwoopedido' => 'integer',
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