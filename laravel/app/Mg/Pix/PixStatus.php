<?php
/**
 * Created by php artisan gerador:model.
 * Date: 26/Dec/2020 08:55:22
 */

namespace Mg\Pix;

use Mg\MgModel;
use Mg\Pix\PixCob;
use Mg\Usuario\Usuario;

class PixStatus extends MgModel
{
    protected $table = 'tblpixstatus';
    protected $primaryKey = 'codpixstatus';


    protected $fillable = [
        'pixstatus'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codpixstatus' => 'integer',
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
        return $this->hasMany(PixCob::class, 'codpixstatus', 'codpixstatus');
    }

}