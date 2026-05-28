<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:23:32
 */

namespace Mg\ValeCompra;

use Mg\MgModel;
use Mg\ValeCompra\ValeCompra;
use Mg\ValeCompra\ValeCompraModeloProdutoBarra;
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
        'inativo',
        'modelo',
        'observacoes',
        'total',
        'totalprodutos',
        'turma'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'ano' => 'integer',
        'codpessoafavorecido' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codvalecompramodelo' => 'integer',
        'criacao' => 'datetime',
        'desconto' => 'float',
        'inativo' => 'datetime',
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
        return $this->hasMany(ValeCompraModeloProdutoBarra::class, 'codvalecompramodelo', 'codvalecompramodelo');
    }

}
