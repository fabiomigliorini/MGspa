<?php
/**
 * Created by php artisan gerador:model.
 * Date: 29/Dec/2023 20:33:45
 */

namespace Mg\Estoque;

use Mg\MgModel;
use Mg\Estoque\EstoqueMovimento;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Usuario\Usuario;

class EstoqueMovimentoTipo extends MgModel
{
    const PRECO_INFORMADO = 1;
    const PRECO_MEDIO = 2;
    const PRECO_ORIGEM = 3;

    const AJUSTE = 1002;

    protected $table = 'tblestoquemovimentotipo';
    protected $primaryKey = 'codestoquemovimentotipo';


    protected $fillable = [
        'atualizaultimaentrada',
        'codestoquemovimentotipoorigem',
        'descricao',
        'manual',
        'preco',
        'sigla',
        'transferencia'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'atualizaultimaentrada' => 'boolean',
        'codestoquemovimentotipo' => 'integer',
        'codestoquemovimentotipoorigem' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'manual' => 'boolean',
        'preco' => 'integer',
        'transferencia' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function EstoqueMovimentoTipoOrigem()
    {
        return $this->belongsTo(EstoqueMovimentoTipo::class, 'codestoquemovimentotipoorigem', 'codestoquemovimentotipo');
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
    public function EstoqueMovimentoS()
    {
        return $this->hasMany(EstoqueMovimento::class, 'codestoquemovimentotipo', 'codestoquemovimentotipo');
    }

    public function EstoqueMovimentoTipoDestinoS()
    {
        return $this->hasMany(EstoqueMovimentoTipo::class, 'codestoquemovimentotipoorigem', 'codestoquemovimentotipo');
    }

    public function NaturezaOperacaoS()
    {
        return $this->hasMany(NaturezaOperacao::class, 'codestoquemovimentotipo', 'codestoquemovimentotipo');
    }

}