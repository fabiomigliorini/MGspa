<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:26:23
 */

namespace Mg\Filial;

use Mg\MgModel;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;

class Empresa extends MgModel
{
    const MODOEMISSAONFCE_NORMAL = 1;
    const MODOEMISSAONFCE_OFFLINE = 9;

    protected $table = 'tblempresa';
    protected $primaryKey = 'codempresa';


    protected $fillable = [
        'contingenciadata',
        'contingenciajustificativa',
        'empresa',
        'fatorencargos',
        'modoemissaonfce'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codempresa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'contingenciadata' => 'datetime',
        'criacao' => 'datetime',
        'fatorencargos' => 'float',
        'modoemissaonfce' => 'integer'
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
    public function FilialS()
    {
        return $this->hasMany(Filial::class, 'codempresa', 'codempresa');
    }

}
