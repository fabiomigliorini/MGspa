<?php
/**
 * Created by php artisan gerador:model.
 * Date: 26/Jan/2021 08:31:15
 */

namespace Mg\Mdfe;

use Mg\MgModel;
use Mg\Mdfe\Mdfe;
use Mg\Usuario\Usuario;

class MdfeStatus extends MgModel
{
    const EM_DIGITACAO = 1;
    const TRANSMITIDO = 2;
    const AUTORIZADO = 3;
    const NAO_AUTORIZADO = 4;
    const ENCERRADO = 5;

    protected $table = 'tblmdfestatus';
    protected $primaryKey = 'codmdfestatus';


    protected $fillable = [
        'mdfestatus',
        'sigla'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codmdfestatus' => 'integer',
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
    public function MdfeS()
    {
        return $this->hasMany(Mdfe::class, 'codmdfestatus', 'codmdfestatus');
    }

}
