<?php
/**
 * Created by php artisan gerador:model.
 * Date: 26/Dec/2020 08:55:19
 */

namespace Mg\Pix;

use Mg\MgModel;
use Mg\Negocio\Negocio;
use Mg\Pix\PixStatus;
use Mg\Portador\Portador;
use Mg\Usuario\Usuario;

class PixCob extends MgModel
{
    protected $table = 'tblpixcob';
    protected $primaryKey = 'codpixcob';


    protected $fillable = [
        'cnpj',
        'codnegocio',
        'codpixstatus',
        'codportador',
        'cpf',
        'expiracao',
        'solicitacaopagador',
        'txid',
        'valororiginal'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'cnpj' => 'float',
        'codnegocio' => 'integer',
        'codpixcob' => 'integer',
        'codpixstatus' => 'integer',
        'codportador' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'cpf' => 'float',
        'expiracao' => 'integer',
        'valororiginal' => 'float'
    ];


    // Chaves Estrangeiras
    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function PixStatus()
    {
        return $this->belongsTo(PixStatus::class, 'codpixstatus', 'codpixstatus');
    }

    public function Portador()
    {
        return $this->belongsTo(Portador::class, 'codportador', 'codportador');
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