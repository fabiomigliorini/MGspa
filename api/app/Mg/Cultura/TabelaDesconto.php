<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 19:33:30
 */

namespace Mg\Cultura;

use Mg\MgModel;
use Mg\Cultura\Cultura;

class TabelaDesconto extends MgModel
{
    protected $table = 'tbltabeladesconto';
    protected $primaryKey = 'codtabeladesconto';

    protected $appends = ['usuariocriacao', 'usuarioalteracao'];


    protected $fillable = [
        'codcultura',
        'faixafim',
        'faixainicio',
        'inativo',
        'percentualdesconto',
        'tipo'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcultura' => 'integer',
        'codtabeladesconto' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'faixafim' => 'float',
        'faixainicio' => 'float',
        'inativo' => 'datetime',
        'percentualdesconto' => 'float'
    ];


    // Chaves Estrangeiras
    public function Cultura()
    {
        return $this->belongsTo(Cultura::class, 'codcultura', 'codcultura');
    }

}
