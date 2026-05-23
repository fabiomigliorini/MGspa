<?php
/**
 * Created by php artisan gerador:model.
 * Date: 14/Nov/2020 08:48:00
 */

namespace Mg\Lio;

use Mg\MgModel;
use Mg\Lio\LioBandeiraCartao;
use Mg\Lio\LioPedido;
use Mg\Lio\LioProduto;
use Mg\Lio\LioTerminal;
use Mg\Usuario\Usuario;

class LioPedidoPagamento extends MgModel
{
    protected $table = 'tblliopedidopagamento';
    protected $primaryKey = 'codliopedidopagamento';


    protected $fillable = [
        'autorizacao',
        'autorizada',
        'cartao',
        'codigov40',
        'codliobandeiracartao',
        'codliopedido',
        'codlioproduto',
        'codlioterminal',
        'nome',
        'nsu',
        'parcelas',
        'transacao',
        'uuid',
        'valor'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'transacao'
    ];

    protected $casts = [
        'autorizacao' => 'float',
        'autorizada' => 'boolean',
        'codigov40' => 'integer',
        'codliobandeiracartao' => 'integer',
        'codliopedido' => 'integer',
        'codliopedidopagamento' => 'integer',
        'codlioproduto' => 'integer',
        'codlioterminal' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'nsu' => 'float',
        'parcelas' => 'integer',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function LioBandeiraCartao()
    {
        return $this->belongsTo(LioBandeiraCartao::class, 'codliobandeiracartao', 'codliobandeiracartao');
    }

    public function LioPedido()
    {
        return $this->belongsTo(LioPedido::class, 'codliopedido', 'codliopedido');
    }

    public function LioProduto()
    {
        return $this->belongsTo(LioProduto::class, 'codlioproduto', 'codlioproduto');
    }

    public function LioTerminal()
    {
        return $this->belongsTo(LioTerminal::class, 'codlioterminal', 'codlioterminal');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

}