<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Jun/2020 14:54:40
 */

namespace Mg\ValeCompra;

use Mg\MgModel;
use Mg\ValeCompra\ValeCompraFormaPagamento;
use Mg\ValeCompra\ValeCompraProdutoBarra;
use Mg\Filial\Filial;
use Mg\Pessoa\Pessoa;
use Mg\Titulo\Titulo;
use Mg\Usuario\Usuario;
use Mg\ValeCompra\ValeCompraModelo;

class ValeCompra extends MgModel
{
    protected $table = 'tblvalecompra';
    protected $primaryKey = 'codvalecompra';


    protected $fillable = [
        'aluno',
        'codfilial',
        'codpessoa',
        'codpessoafavorecido',
        'codtitulo',
        'codvalecompramodelo',
        'desconto',
        'observacoes',
        'total',
        'totalprodutos',
        'turma'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codfilial' => 'integer',
        'codpessoa' => 'integer',
        'codpessoafavorecido' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codvalecompra' => 'integer',
        'codvalecompramodelo' => 'integer',
        'desconto' => 'float',
        'total' => 'float',
        'totalprodutos' => 'float'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function PessoaFavorecido()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoafavorecido', 'codpessoa');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function ValeCompraModelo()
    {
        return $this->belongsTo(ValeCompraModelo::class, 'codvalecompramodelo', 'codvalecompramodelo');
    }


    // Tabelas Filhas
    public function ValeCompraFormaPagamentoS()
    {
        return $this->hasMany(ValeCompraFormaPagamento::class, 'codvalecompra', 'codvalecompra');
    }

    public function ValeCompraProdutoBarraS()
    {
        return $this->hasMany(ValeCompraProdutoBarra::class, 'codvalecompra', 'codvalecompra');
    }

}