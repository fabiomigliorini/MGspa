<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Feb/2026 16:58:57
 */

namespace Mg\Pdv;

use Mg\MgModel;
use Mg\Titulo\LiquidacaoTitulo;
use Mg\Negocio\Negocio;
use Mg\PagarMe\PagarMePagamento;
use Mg\PagarMe\PagarMePedido;
use Mg\Pix\Pix;
use Mg\Pix\PixCob;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;
use Mg\Portador\Portador;
use Mg\Filial\Setor;

class Pdv extends MgModel
{
    protected $table = 'tblpdv';
    protected $primaryKey = 'codpdv';


    protected $fillable = [
        'alocacao',
        'apelido',
        'autorizado',
        'codfilial',
        'codportador',
        'codsetor',
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
        'codsetor' => 'integer',
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

    public function Setor()
    {
        return $this->belongsTo(Setor::class, 'codsetor', 'codsetor');
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

}