<?php
/**
 * Created by php artisan gerador:model.
 * Date: 17/Jul/2020 10:14:59
 */

namespace Mg\Dfe;

use Mg\MgModel;
use Mg\NotaFiscalTerceiro\DistribuicaoDfe;
use Mg\Usuario\Usuario;

class DfeTipo extends MgModel
{
    protected $table = 'tbldfetipo';
    protected $primaryKey = 'coddfetipo';


    protected $fillable = [
        'dfetipo',
        'schemaxml'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'coddfetipo' => 'integer',
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
    public function DistribuicaoDfeS()
    {
        return $this->hasMany(DistribuicaoDfe::class, 'coddfetipo', 'coddfetipo');
    }

}