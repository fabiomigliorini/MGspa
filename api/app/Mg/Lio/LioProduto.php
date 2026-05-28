<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:29:57
 */

namespace Mg\Lio;

use Mg\MgModel;
use Mg\Lio\LioPedidoPagamento;
use Mg\Usuario\Usuario;

class LioProduto extends MgModel
{
    protected $table = 'tbllioproduto';
    protected $primaryKey = 'codlioproduto';


    protected $fillable = [
        'codigoprimario',
        'codigosecundario',
        'lioproduto',
        'nomeprimario',
        'nomesecundario'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codigoprimario' => 'integer',
        'codigosecundario' => 'integer',
        'codlioproduto' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
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


    // Tabelas Filhas
    public function LioPedidoPagamentoS()
    {
        return $this->hasMany(LioPedidoPagamento::class, 'codlioproduto', 'codlioproduto');
    }

}
