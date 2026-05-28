<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:25:40
 */

namespace Mg\Estoque;

use Mg\MgModel;
use Mg\Estoque\EstoqueMovimento;
use Mg\Estoque\EstoqueMes;
use Mg\Estoque\EstoqueMovimentoTipo;
use Mg\Estoque\EstoqueSaldoConferencia;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\NotaFiscal\NotaFiscalProdutoBarra;
use Mg\Usuario\Usuario;

class EstoqueMovimento extends MgModel
{
    protected $table = 'tblestoquemovimento';
    protected $primaryKey = 'codestoquemovimento';


    protected $fillable = [
        'codestoquemes',
        'codestoquemovimentoorigem',
        'codestoquemovimentotipo',
        'codestoquesaldoconferencia',
        'codnegocioprodutobarra',
        'codnotafiscalprodutobarra',
        'data',
        'entradaquantidade',
        'entradavalor',
        'manual',
        'observacoes',
        'saidaquantidade',
        'saidavalor'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codestoquemes' => 'integer',
        'codestoquemovimento' => 'integer',
        'codestoquemovimentoorigem' => 'integer',
        'codestoquemovimentotipo' => 'integer',
        'codestoquesaldoconferencia' => 'integer',
        'codnegocioprodutobarra' => 'integer',
        'codnotafiscalprodutobarra' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'data' => 'datetime',
        'entradaquantidade' => 'float',
        'entradavalor' => 'float',
        'manual' => 'boolean',
        'saidaquantidade' => 'float',
        'saidavalor' => 'float'
    ];


    // Chaves Estrangeiras
    public function EstoqueMes()
    {
        return $this->belongsTo(EstoqueMes::class, 'codestoquemes', 'codestoquemes');
    }

    public function EstoqueMovimentoOrigem()
    {
        return $this->belongsTo(EstoqueMovimento::class, 'codestoquemovimentoorigem', 'codestoquemovimento');
    }

    public function EstoqueMovimentoTipo()
    {
        return $this->belongsTo(EstoqueMovimentoTipo::class, 'codestoquemovimentotipo', 'codestoquemovimentotipo');
    }

    public function EstoqueSaldoConferencia()
    {
        return $this->belongsTo(EstoqueSaldoConferencia::class, 'codestoquesaldoconferencia', 'codestoquesaldoconferencia');
    }

    public function NegocioProdutoBarra()
    {
        return $this->belongsTo(NegocioProdutoBarra::class, 'codnegocioprodutobarra', 'codnegocioprodutobarra');
    }

    public function NotaFiscalProdutoBarra()
    {
        return $this->belongsTo(NotaFiscalProdutoBarra::class, 'codnotafiscalprodutobarra', 'codnotafiscalprodutobarra');
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
    public function EstoqueMovimentoOrigemS()
    {
        return $this->hasMany(EstoqueMovimento::class, 'codestoquemovimentoorigem', 'codestoquemovimento');
    }

}
