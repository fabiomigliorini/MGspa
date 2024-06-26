<?php
/**
 * Created by php artisan gerador:model.
 * Date: 02/Jan/2024 17:27:56
 */

namespace Mg\Pix;

use Mg\MgModel;
use Mg\Pix\Pix;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Negocio\Negocio;
use Mg\Pix\PixCobStatus;
use Mg\Portador\Portador;
use Mg\Usuario\Usuario;
use Mg\Pdv\Pdv;

class PixCob extends MgModel
{
    protected $table = 'tblpixcob';
    protected $primaryKey = 'codpixcob';


    protected $fillable = [
        'cnpj',
        'codnegocio',
        'codpdv',
        'codpixcobstatus',
        'codportador',
        'cpf',
        'expiracao',
        'location',
        'locationid',
        'nome',
        'qrcode',
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
        'codpdv' => 'integer',
        'codpixcob' => 'integer',
        'codpixcobstatus' => 'integer',
        'codportador' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'cpf' => 'float',
        'expiracao' => 'integer',
        'locationid' => 'integer',
        'valororiginal' => 'float'
    ];


    // Chaves Estrangeiras
    public function Negocio()
    {
        return $this->belongsTo(Negocio::class, 'codnegocio', 'codnegocio');
    }

    public function Pdv()
    {
        return $this->belongsTo(Pdv::class, 'codpdv', 'codpdv');
    }

    public function PixCobStatus()
    {
        return $this->belongsTo(PixCobStatus::class, 'codpixcobstatus', 'codpixcobstatus');
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


    // Tabelas Filhas
    public function NegocioFormaPagamentoS()
    {
        return $this->hasMany(NegocioFormaPagamento::class, 'codpixcob', 'codpixcob');
    }

    public function PixS()
    {
        return $this->hasMany(Pix::class, 'codpixcob', 'codpixcob');
    }

}