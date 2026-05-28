<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:23:46
 */

namespace Mg\Boleto;

use Mg\MgModel;
use Mg\Boleto\BoletoRetorno;
use Mg\Boleto\BoletoTipoOcorrencia;
use Mg\Usuario\Usuario;

class BoletoMotivoOcorrencia extends MgModel
{
    protected $table = 'tblboletomotivoocorrencia';
    protected $primaryKey = 'codboletomotivoocorrencia';


    protected $fillable = [
        'codboletotipoocorrencia',
        'motivo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codboletomotivoocorrencia' => 'integer',
        'codboletotipoocorrencia' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function BoletoTipoOcorrencia()
    {
        return $this->belongsTo(BoletoTipoOcorrencia::class, 'codboletotipoocorrencia', 'codboletotipoocorrencia');
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
    public function BoletoRetornoS()
    {
        return $this->hasMany(BoletoRetorno::class, 'codboletomotivoocorrencia', 'codboletomotivoocorrencia');
    }

}
