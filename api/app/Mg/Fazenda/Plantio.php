<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 19:33:41
 */

namespace Mg\Fazenda;

use Mg\MgModel;
use Mg\Safra\CargaColheita;
use Mg\Safra\Safra;
use Mg\Fazenda\Talhao;
use Mg\Cultura\Variedade;

class Plantio extends MgModel
{
    protected $table = 'tblplantio';
    protected $primaryKey = 'codplantio';


    protected $fillable = [
        'areaplantada',
        'codsafra',
        'codtalhao',
        'codvariedade',
        'dataplantio',
        'inativo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'areaplantada' => 'float',
        'codplantio' => 'integer',
        'codsafra' => 'integer',
        'codtalhao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'codvariedade' => 'integer',
        'criacao' => 'datetime',
        'dataplantio' => 'date',
        'inativo' => 'datetime'
    ];


    // Chaves Estrangeiras
    public function Safra()
    {
        return $this->belongsTo(Safra::class, 'codsafra', 'codsafra');
    }

    public function Talhao()
    {
        return $this->belongsTo(Talhao::class, 'codtalhao', 'codtalhao');
    }

    public function Variedade()
    {
        return $this->belongsTo(Variedade::class, 'codvariedade', 'codvariedade');
    }


    // Tabelas Filhas
    public function CargaColheitaS()
    {
        return $this->hasMany(CargaColheita::class, 'codplantio', 'codplantio');
    }

}
