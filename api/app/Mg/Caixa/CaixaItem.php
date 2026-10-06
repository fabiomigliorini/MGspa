<?php

namespace Mg\Caixa;

use Mg\MgModel;
use Mg\Portador\PortadorMovimento;
use Mg\Usuario\Usuario;

/**
 * Item do caixa (doc-4, "Itens do caixa"): o que se controla no portador em
 * especie alem do dinheiro (chips, ingressos).
 * Conta como cedula: o saldo inclui o item a valor de face, a contagem e' por
 * preco x quantidade e o unico lancamento e' a entrada (com sinal), tipo I no
 * movimento do portador. Cadastro minimo (nome): os outros itens (ingressos,
 * maquinetas de parceiros) trazem os campos que precisarem quando chegar a vez.
 */
class CaixaItem extends MgModel
{
    protected $table = 'tblcaixaitem';
    protected $primaryKey = 'codcaixaitem';

    protected $fillable = [
        'item',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcaixaitem' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
    ];

    // Chaves Estrangeiras
    public function UsuarioCriacao()
    {
        return $this->belongsTo(Usuario::class, 'codusuariocriacao', 'codusuario');
    }

    public function UsuarioAlteracao()
    {
        return $this->belongsTo(Usuario::class, 'codusuarioalteracao', 'codusuario');
    }

    // Tabelas Filhas
    public function PortadorMovimentoS()
    {
        return $this->hasMany(PortadorMovimento::class, 'codcaixaitem', 'codcaixaitem');
    }
}
