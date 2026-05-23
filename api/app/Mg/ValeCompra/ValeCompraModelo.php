<?php
/**
 * Created by php artisan gerador:model.
 * Date: 21/Jul/2020 11:58:29
 */

namespace Mg\ValeCompra;

use Mg\MgModel;
use Mg\ValeCompra\ValeCompra;
use Mg\Valecompramodeloprodutobarra\Valecompramodeloprodutobarra;
use Mg\Pessoa\Pessoa;
use Mg\Usuario\Usuario;

class ValeCompraModelo extends MgModel
{
    protected $table = 'tblvalecompramodelo';
    protected $primaryKey = 'codvalecompramodelo';


    protected $fillable = [
        'ano',
        'codpessoafavorecido',
        'desconto',
        'modelo',
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
        'ano' => 'integer',
        'codpessoafavorecido' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codvalecompramodelo' => 'integer',
        'desconto' => 'float',
        'total' => 'float',
        'totalprodutos' => 'float'
    ];


    // Chaves Estrangeiras
    public function PessoaFavorecido()
    {
        return $this->belongsTo(Pessoa::class, 'codpessoafavorecido', 'codpessoa');
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
    public function ValeCompraS()
    {
        return $this->hasMany(ValeCompra::class, 'codvalecompramodelo', 'codvalecompramodelo');
    }

    public function ValecompramodeloprodutobarraS()
    {
        return $this->hasMany(Valecompramodeloprodutobarra::class, 'codvalecompramodelo', 'codvalecompramodelo');
    }

}