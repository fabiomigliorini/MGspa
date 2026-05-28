<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:39:52
 */

namespace Mg\Filial;

use Mg\MgModel;
use Mg\Usuario\Usuario;

class ParametrosGerais extends MgModel
{
    protected $table = 'tblparametrosgerais';
    protected $primaryKey = 'codparametrosgerais';


    protected $fillable = [
        'transacaofinal',
        'transacaoinicial'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codparametrosgerais' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'transacaofinal' => 'date',
        'transacaoinicial' => 'date'
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

}
