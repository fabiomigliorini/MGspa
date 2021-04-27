<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/Apr/2021 16:34:13
 */

namespace Mg\Stone;

use Mg\MgModel;
use Mg\Stone\StonePreTransacao;
use Mg\Stone\StoneTransacao;
use Mg\Stone\StonePos;
use Mg\Filial\Filial;
use Mg\Usuario\Usuario;

class StoneFilial extends MgModel
{
    protected $table = 'tblstonefilial';
    protected $primaryKey = 'codstonefilial';


    protected $fillable = [
        'chaveprivada',
        'codfilial',
        'datatoken',
        'disponivelloja',
        'establishmentid',
        'inativo',
        'stonecode',
        'token'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'datatoken',
        'inativo'
    ];

    protected $casts = [
        'codfilial' => 'integer',
        'codstonefilial' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'disponivelloja' => 'boolean',
        'stonecode' => 'float'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
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
    public function StonePosS()
    {
        return $this->hasMany(StonePos::class, 'codstonefilial', 'codstonefilial');
    }

    public function StonePreTransacaoS()
    {
        return $this->hasMany(StonePreTransacao::class, 'codstonefilial', 'codstonefilial');
    }

    public function StoneTransacaoS()
    {
        return $this->hasMany(StoneTransacao::class, 'codstonefilial', 'codstonefilial');
    }

}