<?php
/**
 * Created by php artisan gerador:model.
 * Date: 09/Dec/2024 11:31:58
 */

namespace Mg\Pdv;

use Mg\MgModel;
use Mg\Negocio\Negocio;
use Mg\PagarMe\PagarMePagamento;
use Mg\PagarMe\PagarMePedido;
use Mg\Pix\Pix;
use Mg\Pix\PixCob;
use Mg\Titulo\LiquidacaoTitulo;
use Mg\Saurus\SaurusPdv;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;
use Mg\Portador\Portador;

class Pdv extends MgModel
{
    protected $table = 'tblpdv';
    protected $primaryKey = 'codpdv';


    protected $fillable = [
        'apelido',
        'autorizado',
        'codfilial',
        'codportador',
        'desktop',
        'inativo',
        'ip',
        'latitude',
        'longitude',
        'navegador',
        'observacoes',
        'plataforma',
        'precisao',
        'uuid',
        'versaonavegador'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'autorizado' => 'boolean',
        'codfilial' => 'integer',
        'codpdv' => 'integer',
        'codportador' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'desktop' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'precisao' => 'float'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
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
    public function LiquidacaoTituloS()
    {
        return $this->hasMany(LiquidacaoTitulo::class, 'codpdv', 'codpdv');
    }

    public function NegocioS()
    {
        return $this->hasMany(Negocio::class, 'codpdv', 'codpdv');
    }

    public function PagarMePagamentoS()
    {
        return $this->hasMany(PagarMePagamento::class, 'codpdv', 'codpdv');
    }

    public function PagarMePedidoS()
    {
        return $this->hasMany(PagarMePedido::class, 'codpdv', 'codpdv');
    }

    public function PixS()
    {
        return $this->hasMany(Pix::class, 'codpdv', 'codpdv');
    }

    public function PixCobS()
    {
        return $this->hasMany(PixCob::class, 'codpdv', 'codpdv');
    }

    public function SaurusPdvS()
    {
        return $this->hasMany(SaurusPdv::class, 'codpdv', 'codpdv');
    }

}