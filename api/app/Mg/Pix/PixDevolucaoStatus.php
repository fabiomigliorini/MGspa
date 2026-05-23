<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/Dec/2020 15:15:30
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

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'codpixdevolucaostatus' => 'integer',
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
    public function PixDevolucaoS()
    {
        return $this->hasMany(PixDevolucao::class, 'codpixdevolucaostatus', 'codpixdevolucaostatus');
    }

}