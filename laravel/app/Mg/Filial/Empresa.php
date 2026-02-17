<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Feb/2026 21:58:54
 */

namespace Mg\Filial;

use Mg\MgModel;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;

class Empresa extends MgModel
{
    protected $table = 'tblempresa';
    protected $primaryKey = 'codempresa';


    protected $fillable = [
        'contingenciadata',
        'contingenciajustificativa',
        'empresa',
        'modoemissaonfce'
    ];

    protected $dates = [
        'alteracao',
        'contingenciadata',
        'criacao'
    ];

    protected $casts = [
        'codempresa' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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