<?php

namespace Mg\Negocio;

use Mg\MgModel;

class Negocio extends MGModel
{
    protected $table = 'tblnegocio';
    protected $primaryKey = 'codnegocio';
    protected $fillable = [
        'codpessoa',
        'codfilial',
        'codestoquelocal',
        'lancamento',
        'codpessoavendedor',
        'codoperacao',
        'codnegociostatus',
        'observacoes',
        'codusuario',
        'valordeconto',
        'entrega',
        'acertoentrega',
        'codusuarioacertoentrega',
        'codnaturezaoperacao',
        'valorprodutos',
        'valortotal',
        'valorprazo',
        'valoravista',
        'codestoquelocaldestino'
    ];
    protected $dates = [
        'recebimeto',
        'alteracao',
        'criacao'
    ];

    // Chaves Estrangeiras
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

    public function EstoqueLocal()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocal', 'codestoquelocal');
    }

    public function EstoqueLocalDestino()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocaldestino', 'codestoquelocal');
    }

    // Tabelas Filhas
    public function NegocioProdutoBarraS()
    {
        return $this->hasMany(NegocioProdutoBarra::class, 'codnegocio', 'codnegocio');
    }

    public function NegocioCaixaMercadoriaS()
    {
        return $this->hasMany(NegocioCaixaMercadoria::class, 'codnegocio', 'codnegocio');
    }


}
