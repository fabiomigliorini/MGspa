<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:30:35
 */

namespace Mg\Pix;

use Mg\MgModel;
use Mg\Pix\PixDevolucao;
use Mg\Usuario\Usuario;

class PixDevolucaoStatus extends MgModel
{
    protected $table = 'tblpixdevolucaostatus';
    protected $primaryKey = 'codpixdevolucaostatus';


    protected $fillable = [
        'pixdevolucaostatus'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codpixdevolucaostatus' => 'integer',
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
    public function PixDevolucaoS()
    {
        return $this->hasMany(PixDevolucao::class, 'codpixdevolucaostatus', 'codpixdevolucaostatus');
    }

}
