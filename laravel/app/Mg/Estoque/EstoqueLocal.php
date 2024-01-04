<?php
/**
 * Created by php artisan gerador:model.
 * Date: 22/Dec/2023 09:58:16
 */

namespace Mg\Estoque;

use Mg\MgModel;
use Mg\Estoque\EstoqueLocalProdutoVariacao;
use Mg\Negocio\Negocio;
use Mg\NotaFiscal\NotaFiscal;
use Mg\Pedido\Pedido;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;

class EstoqueLocal extends MgModel
{
    protected $table = 'tblestoquelocal';
    protected $primaryKey = 'codestoquelocal';


    protected $fillable = [
        'codfilial',
        'controlaestoque',
        'deposito',
        'estoquelocal',
        'inativo',
        'sigla'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'codestoquelocal' => 'integer',
        'codfilial' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'controlaestoque' => 'boolean',
        'deposito' => 'boolean'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
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
    public function EstoqueLocalProdutoVariacaoS()
    {
        return $this->hasMany(EstoqueLocalProdutoVariacao::class, 'codestoquelocal', 'codestoquelocal');
    }

    public function NegocioS()
    {
        return $this->hasMany(Negocio::class, 'codestoquelocal', 'codestoquelocal');
    }

    public function NegocioDestinoS()
    {
        return $this->hasMany(Negocio::class, 'codestoquelocaldestino', 'codestoquelocal');
    }

    public function NotaFiscalS()
    {
        return $this->hasMany(NotaFiscal::class, 'codestoquelocal', 'codestoquelocal');
    }

    public function PedidoS()
    {
        return $this->hasMany(Pedido::class, 'codestoquelocal', 'codestoquelocal');
    }

    public function PedidoOrigemS()
    {
        return $this->hasMany(Pedido::class, 'codestoquelocalorigem', 'codestoquelocal');
    }

}