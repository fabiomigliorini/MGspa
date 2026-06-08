<?php
/**
 * Created by php artisan gerador:model.
 * Date: 03/Jun/2026 23:47:42
 */

namespace Mg\Contrato;

use Mg\MgModel;
use Mg\Contrato\Contrato;

class ContratoPagamento extends MgModel
{
    protected $table = 'tblcontratopagamento';
    protected $primaryKey = 'codcontratopagamento';


    protected $fillable = [
        'codcontrato',
        'data',
        'inativo',
        'observacao',
        'valor'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcontrato' => 'integer',
        'codcontratopagamento' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'data' => 'date',
        'inativo' => 'datetime',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function Contrato()
    {
        return $this->belongsTo(Contrato::class, 'codcontrato', 'codcontrato');
    }

}
