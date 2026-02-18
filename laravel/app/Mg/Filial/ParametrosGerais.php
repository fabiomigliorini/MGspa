<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Feb/2026 10:50:23
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

    protected $dates = [
        'alteracao',
        'criacao',
        'transacaofinal',
        'transacaoinicial'
    ];

    protected $casts = [
        'codparametrosgerais' => 'integer',
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

}