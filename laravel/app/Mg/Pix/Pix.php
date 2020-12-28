<?php
/**
 * Created by php artisan gerador:model.
 * Date: 28/Dec/2020 15:15:44
 */

namespace Mg\Pix;

use Mg\MgModel;
use Mg\Pix\PixDevolucao;
use Mg\Pix\PixCob;
use Mg\Portador\Portador;
use Mg\Usuario\Usuario;

class Pix extends MgModel
{
    protected $table = 'tblpix';
    protected $primaryKey = 'codpix';


    protected $fillable = [
        'cnpj',
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
        'codpix' => 'integer',
        'codpixcob' => 'integer',
        'codportador' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'cpf' => 'float',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
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

}