<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/Dec/2020 16:15:14
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codpixcobstatus' => 'integer',
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
    public function PixCobS()
    {
        return $this->hasMany(PixCob::class, 'codpixcobstatus', 'codpixcobstatus');
    }

}