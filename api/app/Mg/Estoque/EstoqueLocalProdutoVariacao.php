<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:34:16
 */

namespace Mg\Estoque;

use Mg\MgModel;
use Mg\Estoque\EstoqueLocalProdutoVariacaoVenda;
use Mg\Estoque\EstoqueSaldo;
use Mg\Estoque\EstoqueLocal;
use Mg\Produto\ProdutoVariacao;
use Mg\Usuario\Usuario;

class EstoqueLocalProdutoVariacao extends MgModel
{
    protected $table = 'tblestoquelocalprodutovariacao';
    protected $primaryKey = 'codestoquelocalprodutovariacao';


    protected $fillable = [
        'bloco',
        'codestoquelocal',
        'codprodutovariacao',
        'coluna',
        'corredor',
        'estoquemaximo',
        'estoqueminimo',
        'lotetransferencia',
        'prateleira',
        'vencimento',
        'vendaanoquantidade',
        'vendaanovalor',
        'vendabimestrequantidade',
        'vendabimestrevalor',
        'vendadiaquantidadeprevisao',
        'vendasemestrequantidade',
        'vendasemestrevalor',
        'vendaultimocalculo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'bloco' => 'integer',
        'codestoquelocal' => 'integer',
        'codestoquelocalprodutovariacao' => 'integer',
        'codprodutovariacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'coluna' => 'integer',
        'corredor' => 'integer',
        'criacao' => 'datetime',
        'estoquemaximo' => 'integer',
        'estoqueminimo' => 'integer',
        'lotetransferencia' => 'float',
        'prateleira' => 'integer',
        'vencimento' => 'date',
        'vendaanoquantidade' => 'float',
        'vendaanovalor' => 'float',
        'vendabimestrequantidade' => 'float',
        'vendabimestrevalor' => 'float',
        'vendadiaquantidadeprevisao' => 'float',
        'vendasemestrequantidade' => 'float',
        'vendasemestrevalor' => 'float',
        'vendaultimocalculo' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function EstoqueLocal()
    {
        return $this->belongsTo(EstoqueLocal::class, 'codestoquelocal', 'codestoquelocal');
    }

    public function ProdutoVariacao()
    {
        return $this->belongsTo(ProdutoVariacao::class, 'codprodutovariacao', 'codprodutovariacao');
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
    public function EstoqueLocalProdutoVariacaoVendaS()
    {
        return $this->hasMany(EstoqueLocalProdutoVariacaoVenda::class, 'codestoquelocalprodutovariacao', 'codestoquelocalprodutovariacao');
    }

    public function EstoqueSaldoS()
    {
        return $this->hasMany(EstoqueSaldo::class, 'codestoquelocalprodutovariacao', 'codestoquelocalprodutovariacao');
    }

}
