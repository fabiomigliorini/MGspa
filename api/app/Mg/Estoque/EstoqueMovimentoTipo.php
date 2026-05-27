<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:37:06
 */

namespace Mg\Estoque;

use Mg\MgModel;
use Mg\Estoque\EstoqueMovimento;
use Mg\Estoque\EstoqueMovimentoTipo;
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

    protected $casts = [
        'alteracao' => 'datetime',
        'atualizaultimaentrada' => 'boolean',
        'codestoquemovimentotipo' => 'integer',
        'codestoquemovimentotipoorigem' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
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
