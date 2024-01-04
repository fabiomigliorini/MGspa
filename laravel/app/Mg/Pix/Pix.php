<?php
/**
 * Created by php artisan gerador:model.
 * Date: 02/Jan/2024 17:27:45
 */

namespace Mg\Pix;

use Mg\MgModel;
use Mg\Pix\PixDevolucao;
use Mg\Portador\PortadorMovimento;
use Mg\Pix\PixCob;
use Mg\Portador\Portador;
use Mg\Usuario\Usuario;
use Mg\Pdv\Pdv;

class Pix extends MgModel
{
    protected $table = 'tblpix';
    protected $primaryKey = 'codpix';


    protected $fillable = [
        'cnpj',
        'codpdv',
        'codpixcob',
        'codportador',
        'cpf',
        'e2eid',
        'horario',
        'infopagador',
        'nome',
        'txid',
        'valor'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'horario'
    ];

    protected $casts = [
        'cnpj' => 'float',
        'codpdv' => 'integer',
        'codpix' => 'integer',
        'codpixcob' => 'integer',
        'codportador' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'cpf' => 'float',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function Pdv()
    {
        return $this->belongsTo(Pdv::class, 'codpdv', 'codpdv');
    }

    public function PixCob()
    {
        return $this->belongsTo(PixCob::class, 'codpixcob', 'codpixcob');
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
    public function PixDevolucaoS()
    {
        return $this->hasMany(PixDevolucao::class, 'codpix', 'codpix');
    }

    public function PortadorMovimentoS()
    {
        return $this->hasMany(PortadorMovimento::class, 'codpix', 'codpix');
    }

}