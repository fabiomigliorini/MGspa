<?php
/**
 * Created by php artisan gerador:model.
 * Date: 13/Jul/2020 15:53:13
 */

namespace Mg\CupomFiscal;

use Mg\MgModel;
use Mg\CupomFiscal\CupomFiscal;
use Mg\EcfReducaoZ\EcfReducaoZ;
use Mg\Usuario\Usuario;
use Mg\Filial\Filial;

class Ecf extends MgModel
{
    protected $table = 'tblecf';
    protected $primaryKey = 'codecf';


    protected $fillable = [
        'acbrmonitorcaminho',
        'acbrmonitorcaminhorede',
        'bloqueado',
        'codfilial',
        'codusuario',
        'ecf',
        'modelo',
        'numero',
        'serie'
    ];

    protected $dates = [
        'alteracao',
        'bloqueado',
        'criacao'
    ];

    protected $casts = [
        'codecf' => 'integer',
        'codfilial' => 'integer',
        'codusuario' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'numero' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Usuario()
    {
        return $this->belongsTo(Usuario::class, 'codusuario', 'codusuario');
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
    public function CupomFiscalS()
    {
        return $this->hasMany(CupomFiscal::class, 'codecf', 'codecf');
    }

    public function EcfReducaoZS()
    {
        return $this->hasMany(EcfReducaoZ::class, 'codecf', 'codecf');
    }

    public function UsuarioS()
    {
        return $this->hasMany(Usuario::class, 'codecf', 'codecf');
    }

}