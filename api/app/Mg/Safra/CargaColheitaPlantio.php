<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 22:20:44
 */

namespace Mg\Safra;

use Mg\MgModel;
use Mg\Safra\CargaColheita;
use Mg\Fazenda\Plantio;

class CargaColheitaPlantio extends MgModel
{
    protected $table = 'tblcargacolheitaplantio';
    protected $primaryKey = 'codcargacolheitaplantio';


    protected $fillable = [
        'codcargacolheita',
        'codplantio',
        'percentual'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcargacolheita' => 'integer',
        'codcargacolheitaplantio' => 'integer',
        'codplantio' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'percentual' => 'float'
    ];


    // Chaves Estrangeiras
    public function CargaColheita()
    {
        return $this->belongsTo(CargaColheita::class, 'codcargacolheita', 'codcargacolheita');
    }

    public function Plantio()
    {
        return $this->belongsTo(Plantio::class, 'codplantio', 'codplantio');
    }

}
