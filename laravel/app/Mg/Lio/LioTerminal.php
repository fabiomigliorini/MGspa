<?php
/**
 * Created by php artisan gerador:model.
 * Date: 14/Nov/2020 08:48:05
 */

namespace Mg\Lio;

use Mg\MgModel;
use Mg\Lio\LioPedidoPagamento;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;

class LioTerminal extends MgModel
{
    protected $table = 'tbllioterminal';
    protected $primaryKey = 'codlioterminal';


    protected $fillable = [
        'codfilial',
        'lioterminal',
        'terminal'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codfilial' => 'integer',
        'codlioterminal' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'terminal' => 'float'
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
    public function LioPedidoPagamentoS()
    {
        return $this->hasMany(LioPedidoPagamento::class, 'codlioterminal', 'codlioterminal');
    }

}