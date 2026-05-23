<?php
/**
 * Created by php artisan gerador:model.
 * Date: 26/Jan/2021 08:30:59
 */

namespace Mg\Mdfe;

use Mg\MgModel;
use Mg\Cidade\Estado;
use Mg\Mdfe\Mdfe;
use Mg\Usuario\Usuario;

class MdfeEstado extends MgModel
{
    protected $table = 'tblmdfeestado';
    protected $primaryKey = 'codmdfeestado';


    protected $fillable = [
        'codestado',
        'codmdfe'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codestado' => 'integer',
        'codmdfe' => 'integer',
        'codmdfeestado' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Estado()
    {
        return $this->belongsTo(Estado::class, 'codestado', 'codestado');
    }

    public function Mdfe()
    {
        return $this->belongsTo(Mdfe::class, 'codmdfe', 'codmdfe');
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