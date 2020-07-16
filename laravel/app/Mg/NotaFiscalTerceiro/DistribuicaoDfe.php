<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Jul/2020 15:30:32
 */

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiro;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiroEvento;
use Mg\Filial\Filial;

class DistribuicaoDfe extends MgModel
{
    protected $table = 'tbldistribuicaodfe';
    protected $primaryKey = 'coddistribuicaodfe';


    protected $fillable = [
        'codfilial',
        'nfechave',
        'nsu',
        'schema'
    ];

    protected $dates = [
        'alteracao',
        'criacao'
    ];

    protected $casts = [
        'coddistribuicaodfe' => 'integer',
        'codfilial' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer'
    ];


    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }


    // Tabelas Filhas
    public function NotaFiscalTerceiroS()
    {
        return $this->hasMany(NotaFiscalTerceiro::class, 'coddistribuicaodfe', 'coddistribuicaodfe');
    }

    public function NotaFiscalTerceiroEventoS()
    {
        return $this->hasMany(NotaFiscalTerceiroEvento::class, 'coddistribuicaodfe', 'coddistribuicaodfe');
    }

}