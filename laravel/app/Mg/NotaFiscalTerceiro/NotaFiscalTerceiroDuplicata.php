<?php
/**
 * Created by php artisan gerador:model.
 * Date: 18/Jul/2020 08:17:54
 */

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;
use Mg\NotaFiscalTerceiro\NotaFiscalTerceiro;

class NotaFiscalTerceiroDuplicata extends MgModel
{
    protected $table = 'tblnotafiscalterceiroduplicata';
    protected $primaryKey = 'codnotafiscalterceiroduplicata';


    protected $fillable = [
        'codnotafiscalterceiro',
        'codtitulo',
        'numero',
        'valor',
        'vencimento'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'vencimento'
    ];

    protected $casts = [
        'codnotafiscalterceiro' => 'integer',
        'codnotafiscalterceiroduplicata' => 'integer',
        'codtitulo' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'valor' => 'float'
    ];


    // Chaves Estrangeiras
    public function NotaFiscalTerceiro()
    {
        return $this->belongsTo(NotaFiscalTerceiro::class, 'codnotafiscalterceiro', 'codnotafiscalterceiro');
    }

}