<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:30:28
 */

namespace Mg\Pix;

use Mg\MgModel;
use Mg\Pix\Pix;
use Mg\Pix\PixDevolucaoStatus;
use Mg\Usuario\Usuario;

class PixDevolucao extends MgModel
{
    protected $table = 'tblpixdevolucao';
    protected $primaryKey = 'codpixdevolucao';


    protected $fillable = [
        'codpix',
        'codpixdevolucaostatus',
        'id',
        'liquidacao',
        'rtrid',
        'solicitacao',
        'valor'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codpix' => 'integer',
        'codpixdevolucao' => 'integer',
        'codpixdevolucaostatus' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'liquidacao' => 'datetime',
        'solicitacao' => 'datetime',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function Pix()
    {
        return $this->belongsTo(Pix::class, 'codpix', 'codpix');
    }

    public function PixDevolucaoStatus()
    {
        return $this->belongsTo(PixDevolucaoStatus::class, 'codpixdevolucaostatus', 'codpixdevolucaostatus');
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
