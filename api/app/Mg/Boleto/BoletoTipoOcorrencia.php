<?php

/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:23:53
 */

namespace Mg\Boleto;

use Mg\MgModel;
use Mg\Boleto\BoletoMotivoOcorrencia;
use Mg\Usuario\Usuario;

class BoletoTipoOcorrencia extends MgModel
{
    protected $table = 'tblboletotipoocorrencia';
    protected $primaryKey = 'codboletotipoocorrencia';


    protected $fillable = [
        'ocorrencia'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codboletotipoocorrencia' => 'integer',
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
    public function BoletoMotivoOcorrenciaS()
    {
        return $this->hasMany(BoletoMotivoOcorrencia::class, 'codboletotipoocorrencia', 'codboletotipoocorrencia');
    }
}
