<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Nov/2020 16:20:17
 */

namespace Mg\Negocio;

use Mg\MgModel;
use Mg\Negocio\Negocio;
use Mg\Usuario\Usuario;

class NegocioStatus extends MgModel
{
    const ABERTO = 1;
	const FECHADO = 2;
	const CANCELADO = 3;

    protected $table = 'tblnegociostatus';
    protected $primaryKey = 'codnegociostatus';

    protected $fillable = [
        'negociostatus'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codnegociostatus' => 'integer',
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
    public function NegocioS()
    {
        return $this->hasMany(Negocio::class, 'codnegociostatus', 'codnegociostatus');
    }

}
