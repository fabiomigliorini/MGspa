<?php
/**
 * Created by php artisan gerador:model.
 * Date: 29/Dec/2020 15:40:42
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

    protected $dates = [
        'alteracao',
        'criacao',
        'liquidacao',
        'solicitacao'
    ];

    protected $casts = [
        'codpix' => 'integer',
        'codpixdevolucao' => 'integer',
        'codpixdevolucaostatus' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
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