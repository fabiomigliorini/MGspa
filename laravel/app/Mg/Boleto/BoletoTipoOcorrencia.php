<?php
/**
 * Created by php artisan gerador:model.
 * Date: 21/Jun/2020 01:58:12
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codboletotipoocorrencia' => 'integer',
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
    public function BoletoMotivoOcorrenciaS()
    {
        return $this->hasMany(BoletoMotivoOcorrencia::class, 'codboletotipoocorrencia', 'codboletotipoocorrencia');
    }

}