<?php
/**
 * Created by php artisan gerador:model.
 * Date: 21/Jun/2020 01:58:16
 */

namespace Mg\Boleto;

use Mg\MgModel;
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codboletomotivoocorrencia' => 'integer',
        'codboletotipoocorrencia' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
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

}