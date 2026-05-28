<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:41:42
 */

namespace Mg\Woo;

use Mg\MgModel;
use Mg\Negocio\Negocio;
use Mg\Usuario\Usuario;
use Mg\Woo\WooPedido;

class WooPedidoNegocio extends MgModel
{
    protected $table = 'tblwoopedidonegocio';
    protected $primaryKey = 'codwoopedidonegocio';


    protected $fillable = [
        'codnegocio',
        'codwoopedido'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codnegocio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codwoopedido' => 'integer',
        'codwoopedidonegocio' => 'integer',
        'criacao' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function WooPedido()
    {
        return $this->belongsTo(WooPedido::class, 'codwoopedido', 'codwoopedido');
    }

}
