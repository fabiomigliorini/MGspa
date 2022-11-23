<?php
/**
 * Created by php artisan gerador:model.
 * Date: 23/Nov/2022 09:44:44
 */

namespace Mg\PagarMe;

use Mg\MgModel;
use Mg\PagarMe\PagarMePagamento;
use Mg\Filial\Filial;
use Mg\Negocio\Negocio;
use Mg\PagarMe\PagarMePos;
use Mg\Pessoa\Pessoa;

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


    // Tabelas Filhas
    public function PagarMePagamentoS()
    {
        return $this->hasMany(PagarMePagamento::class, 'codpagarmepedido', 'codpagarmepedido');
    }

}