<?php
/**
 * Created by php artisan gerador:model.
 * Date: 09/Dec/2024 11:44:12
 */

namespace Mg\Saurus;

use Mg\MgModel;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Saurus\SaurusBandeira;
use Mg\Saurus\SaurusPedido;
use Mg\Saurus\SaurusPinPad;
use Mg\Usuario\Usuario;

class SaurusPagamento extends MgModel
{
    protected $table = 'tblsauruspagamento';
    protected $primaryKey = 'codsauruspagamento';


    protected $fillable = [
        'autorizacao',
        'cartao',
        'codsaurusbandeira',
        'codsauruspedido',
        'codsauruspinpad',
        'controle',
        'id',
        'modpagamento',
        'nsu',
        'parcelas',
        'status',
        'transacao',
        'valor',
        'valorjuros',
        'valorparcela',
        'valortotal'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'transacao'
    ];

    protected $casts = [
        'codsaurusbandeira' => 'integer',
        'codsauruspagamento' => 'integer',
        'codsauruspedido' => 'integer',
        'codsauruspinpad' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'modpagamento' => 'integer',
        'parcelas' => 'integer',
        'status' => 'integer',
        'valor' => 'float',
        'valorjuros' => 'float',
        'valorparcela' => 'float',
        'valortotal' => 'float'
    ];


    // Chaves Estrangeiras
    public function SaurusBandeira()
    {
        return $this->belongsTo(SaurusBandeira::class, 'codsaurusbandeira', 'codsaurusbandeira');
    }

    public function SaurusPedido()
    {
        return $this->belongsTo(SaurusPedido::class, 'codsauruspedido', 'codsauruspedido');
    }

    public function SaurusPinPad()
    {
        return $this->belongsTo(SaurusPinPad::class, 'codsauruspinpad', 'codsauruspinpad');
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
    public function NegocioFormaPagamentoS()
    {
        return $this->hasMany(NegocioFormaPagamento::class, 'codsauruspagamento', 'codsauruspagamento');
    }

}