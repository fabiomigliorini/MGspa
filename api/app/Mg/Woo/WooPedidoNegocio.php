<?php
/**
 * Created by php artisan gerador:model.
 * Date: 12/Dec/2025 11:16:23
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codnegocio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codwoopedido' => 'integer',
        'codwoopedidonegocio' => 'integer'
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