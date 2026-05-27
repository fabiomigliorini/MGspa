<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:30:09
 */

namespace Mg\Pix;

use Mg\MgModel;
use Mg\Pix\PixCob;
use Mg\Usuario\Usuario;

class PixCobStatus extends MgModel
{
    protected $table = 'tblpixcobstatus';
    protected $primaryKey = 'codpixcobstatus';


    protected $fillable = [
        'pixcobstatus'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codpixcobstatus' => 'integer',
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
    public function PixCobS()
    {
        return $this->hasMany(PixCob::class, 'codpixcobstatus', 'codpixcobstatus');
    }

}
