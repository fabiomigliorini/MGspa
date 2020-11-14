<?php
/**
 * Created by php artisan gerador:model.
 * Date: 14/Nov/2020 08:47:34
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
        'liproduto',
        'nomeprimario',
        'nomesecundario'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codigoprimario' => 'integer',
        'codigosecundario' => 'integer',
        'codlioproduto' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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