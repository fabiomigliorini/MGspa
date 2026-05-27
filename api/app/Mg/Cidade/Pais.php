<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:24:39
 */

namespace Mg\Cidade;

use Mg\MgModel;
use Mg\Cidade\Estado;
use Mg\Usuario\Usuario;

class Pais extends MgModel
{
    protected $table = 'tblpais';
    protected $primaryKey = 'codpais';


    protected $fillable = [
        'codigooficial',
        'inativo',
        'pais',
        'sigla'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codigooficial' => 'integer',
        'codpais' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime'
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
    public function EstadoS()
    {
        return $this->hasMany(Estado::class, 'codpais', 'codpais');
    }

}
