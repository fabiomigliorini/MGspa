<?php
/**
 * Created by php artisan gerador:model.
 * Date: 26/Nov/2022 16:55:58
 */

namespace Mg\PagarMe;

use Mg\MgModel;
use Mg\PagarMe\PagarMePagamento;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Filial\Filial;
use Mg\Negocio\Negocio;
use Mg\PagarMe\PagarMePos;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class PagarMePedido extends MgModel
{
    protected $table = 'tblpagarmepedido';
    protected $primaryKey = 'codpagarmepedido';


    protected $fillable = [
        'codfilial',
        'codnegocio',
        'codpagarmepos',
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
        'valorpago',
        'valorpagoliquido'
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
        'valorpago' => 'float',
        'valorpagoliquido' => 'float'
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
    public function NegocioFormaPagamentoS()
    {
        return $this->hasMany(NegocioFormaPagamento::class, 'codpagarmepedido', 'codpagarmepedido');
    }

    public function PagarMePagamentoS()
    {
        return $this->hasMany(PagarMePagamento::class, 'codpagarmepedido', 'codpagarmepedido');
    }

}