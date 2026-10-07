<?php

namespace Mg\Caixa;

use Mg\ContaContabil\ContaContabil;
use Mg\Filial\Filial;
use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Portador\PortadorMovimento;
use Mg\Usuario\Usuario;

/**
 * Item do caixa (doc-4, "Itens do caixa"): o que se controla no portador em
 * especie alem do dinheiro (chips, ingressos).
 * Conta como cedula: o saldo inclui o item a valor de face, a contagem e' por
 * preco x quantidade e o unico lancamento e' a entrada (com sinal), tipo I no
 * movimento do portador. Cadastro minimo (nome).
 *
 * Modo M, maquineta de parceiro (doc-4, "Itens de parceiro"): sem estoque e
 * fora da contagem; o caixa lanca o total em dinheiro do bordero (tipo M no
 * movimento do portador) e o financeiro paga pela conta corrente da maquineta
 * (CaixaItemContaService), gerando o titulo a pagar com a pessoa, a filial e
 * a conta contabil do item.
 */
class CaixaItem extends MgModel
{
    protected $table = 'tblcaixaitem';
    protected $primaryKey = 'codcaixaitem';

    const MODO_CEDULA = 'C';
    const MODO_MAQUINETA = 'M';

    protected $fillable = [
        'item',
        'modo',
        'codpessoa',
        'codfilial',
        'codcontacontabil',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcaixaitem' => 'integer',
        'codcontacontabil' => 'integer',
        'codfilial' => 'integer',
        'codpessoa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'saldo' => 'float',
        'saldoquantidade' => 'integer',
    ];

    public function ehMaquineta(): bool
    {
        return $this->modo == self::MODO_MAQUINETA;
    }

    // Chaves Estrangeiras
    public function ContaContabil()
    {
        return $this->belongsTo(ContaContabil::class, 'codcontacontabil', 'codcontacontabil');
    }

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Pessoa()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoa', 'codpessoa');
    }

    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    // Tabelas Filhas
    public function CaixaItemAcertoS()
    {
        return $this->hasMany(CaixaItemAcerto::class, 'codcaixaitem', 'codcaixaitem');
    }

    public function PortadorMovimentoS()
    {
        return $this->hasMany(PortadorMovimento::class, 'codcaixaitem', 'codcaixaitem');
    }
}
