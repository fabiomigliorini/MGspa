<?php

namespace Mg\Negocio;

use Mg\MgModel;
use Mg\Pessoa\Pessoa;
use Mg\Titulo\Titulo;
use Mg\Vale\ValeModelo;

/**
 * Vale compras emitido DENTRO de um negocio. PK = codnegociovale.
 *
 * O vale e' um bloco proprio do negocio: nao e' produto e nao e' item de
 * mercadoria (decisao 2 do plano). Por isso mora em tabela propria, com
 * itens proprios, e nao encosta em tblnegocioprodutobarra.
 *
 * Os tres valores (decisoes 21 e 22):
 *   valorprodutos = soma dos itens do vale
 *   valoravulso   = valor digitado a mao
 *   valorvale     = produtos + avulso  <- a FACE, o credito emitido
 * e, separado deles, "valortotal" = a fatia PAGA depois do rateio do
 * desconto (milestone 4). A escola recebe a face; o cliente paga a fatia.
 *
 * So desconto e juros sao rateados para o vale. Frete, seguro e "outras"
 * nao existem aqui: nao se cobra frete nem seguro de um vale compras, entao
 * esses tres ficam inteiros na mercadoria.
 *
 * "codpessoafavorecido" e' a escola — ou Consumidor (1) quando o vale e'
 * ao portador (decisao 13). "inativo" e' soft-delete, igual ao item de
 * mercadoria: o PDV offline nunca apaga linha, carimba a data.
 */
class NegocioVale extends MgModel
{
    protected $table = 'tblnegociovale';
    protected $primaryKey = 'codnegociovale';

    protected $fillable = [
        'aluno',
        'codnegocio',
        'codpessoafavorecido',
        'codtitulo',
        'codvalecompra',
        'codvalemodelo',
        'inativo',
        'observacoes',
        'turma',
        'uuid',
        'validade',
        'valoravulso',
        'valordesconto',
        'valorjuros',
        'valorprodutos',
        'valortotal',
        'valorvale',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codnegocio' => 'integer',
        'codnegociovale' => 'integer',
        'codpessoafavorecido' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codvalecompra' => 'integer',
        'codvalemodelo' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
        'validade' => 'date',
        'valoravulso' => 'float',
        'valordesconto' => 'float',
        'valorjuros' => 'float',
        'valorprodutos' => 'float',
        'valortotal' => 'float',
        'valorvale' => 'float',
    ];

    // Chaves Estrangeiras
    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function PessoaFavorecido()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoafavorecido', 'codpessoa');
    }

    public function Titulo()
    {
        return $this->belongsTo(Titulo::class, 'codtitulo', 'codtitulo');
    }

    public function ValeModelo()
    {
        return $this->belongsTo(ValeModelo::class, 'codvalemodelo', 'codvalemodelo');
    }

    // Tabelas Filhas
    public function NegocioValeProdutoBarraS()
    {
        return $this->hasMany(NegocioValeProdutoBarra::class, 'codnegociovale', 'codnegociovale')
            ->orderBy('ordenacao');
    }
}
