<?php
/**
 * Created by php artisan gerador:model.
 * Date: 13/Oct/2021 14:25:37
 */

namespace Mg\Portador;

use Mg\MgModel;
use Mg\Portador\ExtratoBancario;
use Mg\Portador\PortadorMovimento;

class ExtratoBancarioPortadorMovimento extends MgModel
{
    protected $table = 'tblextratobancarioportadormovimento';
    protected $primaryKey = 'codextratobancarioportadormovimento';


    protected $fillable = [
        'codextratobancario',
        'codportadormovimento'
    ];

    protected $dates = [
        
    ];

    protected $casts = [
        'codextratobancario' => 'integer',
        'codextratobancarioportadormovimento' => 'integer',
        'codportadormovimento' => 'integer'
    ];


    // Chaves Estrangeiras
    public function ExtratoBancario()
    {
        return $this->belongsTo(ExtratoBancario::class, 'codextratobancario', 'codextratobancario');
    }

    public function PortadorMovimento()
    {
        return $this->belongsTo(PortadorMovimento::class, 'codportadormovimento', 'codportadormovimento');
    }

}