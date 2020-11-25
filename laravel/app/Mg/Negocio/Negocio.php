<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Nov/2020 16:21:06
 */

namespace Mg\Negocio;

use Mg\MgModel;
use Mg\Negocio\NegocioCaixaMercadoria;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\NfeTerceiro\NfeTerceiro;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiro;
use Mg\Estoque\EstoqueLocal;
use Mg\Filial\Filial;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Negocio\NegocioStatus;
use Mg\NaturezaOperacao\Operacao;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class Negocio extends MgModel
{
    protected $table = 'tblnegocio';
    protected $primaryKey = 'codnegocio';


    protected $fillable = [
        'acertoentrega',
        'codestoquelocal',
        'codestoquelocaldestino',
        'codfilial',
        'codnaturezaoperacao',
        'codnegociostatus',
        'codoperacao',
        'codpessoa',
        'codpessoavendedor',
        'codusuario',
        'codusuarioacertoentrega',
        'codusuariorecebimento',
        'cpf',
        'entrega',
        'lancamento',
        'observacoes',
        'recebimento',
        'valoraprazo',
        'valoravista',
        'valordesconto',
        'valorprodutos',
        'valortotal'
    ];

    protected $dates = [
        'acertoentrega',
        'alteracao',
        'criacao',
        'lancamento',
        'recebimento'
    ];

    protected $casts = [
        'codestoquelocal' => 'integer',
        'codestoquelocaldestino' => 'integer',
        'codfilial' => 'integer',
        'codnaturezaoperacao' => 'integer',
        'codnegocio' => 'integer',
        'codnegociostatus' => 'integer',
        'codoperacao' => 'integer',
        'codpessoa' => 'integer',
        'codpessoavendedor' => 'integer',
        'codusuario' => 'integer',
        'codusuarioacertoentrega' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codusuariorecebimento' => 'integer',
        'cpf' => 'float',
        'entrega' => 'boolean',
        'valoraprazo' => 'float',
        'valoravista' => 'float',
        'valordesconto' => 'float',
        'valorprodutos' => 'float',
        'valortotal' => 'float'
    ];


    // Chaves Estrangeiras
    public function EstoqueLocal()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocal', 'codestoquelocal');
    }

    public function EstoqueLocalDestino()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocaldestino', 'codestoquelocal');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function NaturezaOperacao()
    {
        return $this->belongsTo(NaturezaOperacao::class, 'codnaturezaoperacao', 'codnaturezaoperacao');
    }

    public function NegocioStatus()
    {
        return $this->belongsTo(NegocioStatus::class, 'codnegociostatus', 'codnegociostatus');
    }

    public function Operacao()
    {
        return $this->belongsTo(Operacao::class, 'codoperacao', 'codoperacao');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function PessoaVendedor()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoavendedor', 'codpessoa');
    }

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'codusuario', 'codusuario');
    }

    public function UsuarioAcertoEntrega()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioacertoentrega', 'codusuario');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioRecebimento()
    {
        return $this->belongsTo(Usuario::class, 'codusuariorecebimento', 'codusuario');
    }


    // Tabelas Filhas
    public function NegocioCaixaMercadoriaS()
    {
        return $this->hasMany(NegocioCaixaMercadoria::class, 'codnegocio', 'codnegocio');
    }

    public function NegocioFormaPagamentoS()
    {
        return $this->hasMany(NegocioFormaPagamento::class, 'codnegocio', 'codnegocio');
    }

    public function NegocioProdutoBarraS()
    {
        return $this->hasMany(NegocioProdutoBarra::class, 'codnegocio', 'codnegocio');
    }

    public function NfeTerceiroS()
    {
        return $this->hasMany(NfeTerceiro::class, 'codnegocio', 'codnegocio');
    }

    public function NotaFiscalTerceiroS()
    {
        return $this->hasMany(NotaFiscalTerceiro::class, 'codnegocio', 'codnegocio');
    }

}