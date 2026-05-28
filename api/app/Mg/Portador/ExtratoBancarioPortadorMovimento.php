<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:35:26
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codextratobancario' => 'integer',
        'codextratobancarioportadormovimento' => 'integer',
        'codportadormovimento' => 'integer',
        'criacao' => 'datetime'
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
