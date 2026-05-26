<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Apr/2024 12:13:13
 */

namespace Mg\PagarMe;

use Mg\MgModel;
use Mg\PagarMe\PagarMePagamento;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Titulo\LiquidacaoTitulo;
use Mg\Filial\Filial;
use Mg\Negocio\Negocio;
use Mg\PagarMe\PagarMePos;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;
use Mg\Pdv\Pdv;

class PagarMePedido extends MgModel
{
    protected $table = 'tblpagarmepedido';
    protected $primaryKey = 'codpagarmepedido';


    protected $fillable = [
        'codfilial',
        'codnegocio',
        'codpagarmepos',
        'codpdv',
        'codpessoa',
        'descricao',
        'fechado',
        'idpedido',
        'jurosloja',
        'parcelas',
        'status',
        'tipo',
        'valor',
        'valorcancelado',
        'valorjuros',
        'valorpago',
        'valorpagoliquido',
        'valorparcela',
        'valortotal'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codfilial' => 'integer',
        'codnegocio' => 'integer',
        'codpagarmepedido' => 'integer',
        'codpagarmepos' => 'integer',
        'codpdv' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'fechado' => 'boolean',
        'jurosloja' => 'boolean',
        'parcelas' => 'integer',
        'status' => 'integer',
        'tipo' => 'integer',
        'valor' => 'float',
        'valorcancelado' => 'float',
        'valorjuros' => 'float',
        'valorpago' => 'float',
        'valorpagoliquido' => 'float',
        'valorparcela' => 'float',
        'valortotal' => 'float'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function PagarMePos()
    {
        return $this->belongsTo(PagarMePos::class, 'codpagarmepos', 'codpagarmepos');
    }

    public function Pdv()
    {
        return $this->belongsTo(Pdv::class, 'codpdv', 'codpdv');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
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
        return $this->hasMany(LiquidacaoTitulo::class, 'codpagarmepedido', 'codpagarmepedido');
    }

    public function NegocioFormaPagamentoS()
    {
        return $this->hasMany(NegocioFormaPagamento::class, 'codpagarmepedido', 'codpagarmepedido');
    }

    public function PagarMePagamentoS()
    {
        return $this->hasMany(PagarMePagamento::class, 'codpagarmepedido', 'codpagarmepedido');
    }

}