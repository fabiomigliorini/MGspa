<?php
/**
 * Created by php artisan gerador:model.
 * Date: 20/Dec/2023 11:51:34
 */

namespace Mg\Pdv;

use Mg\MgModel;
use Mg\Negocio\Negocio;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;

class Pdv extends MgModel
{
    protected $table = 'tblpdv';
    protected $primaryKey = 'codpdv';


    protected $fillable = [
        'apelido',
        'autorizado',
        'codfilial',
        'desktop',
        'inativo',
        'ip',
        'latitude',
        'longitude',
        'navegador',
        'plataforma',
        'precisao',
        'uuid',
        'versaonavegador'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'inativo'
    ];

    protected $casts = [
        'autorizado' => 'boolean',
        'codfilial' => 'integer',
        'codpdv' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'desktop' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'precisao' => 'float'
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
    public function NegocioS()
    {
        return $this->hasMany(Negocio::class, 'codpdv', 'codpdv');
    }

}