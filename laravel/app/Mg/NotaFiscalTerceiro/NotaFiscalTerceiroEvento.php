<?php
/**
 * Created by php artisan gerador:model.
 * Date: 16/Jul/2020 15:19:41
 */

namespace Mg\NotaFiscalTerceiro;

use Mg\MgModel;
use Mg\NotaFiscalTerceiro\DistribuicaoDfe;

class NotaFiscalTerceiroEvento extends MgModel
{
    protected $table = 'tblnotafiscalterceiroevento';
    protected $primaryKey = 'codnotafiscalterceiroevento';


    protected $fillable = [
        'cnpj',
        'coddistribuicaodfe',
        'codorgao',
        'dhevento',
        'dhrecebimento',
        'evento',
        'nfechave',
        'nseqevento',
        'nsu',
        'protocolo',
        'tpevento'
    ];

    protected $dates = [
        'alteracao',
        'criacao',
        'dhevento',
        'dhrecebimento'
    ];

    protected $casts = [
        'cnpj' => 'float',
        'coddistribuicaodfe' => 'integer',
        'codnotafiscalterceiroevento' => 'integer',
        'codorgao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'nseqevento' => 'integer',
        'protocolo' => 'integer',
        'tpevento' => 'integer'
    ];


    // Chaves Estrangeiras
    public function DistribuicaoDfe()
    {
        return $this->belongsTo(DistribuicaoDfe::class, 'coddistribuicaodfe', 'coddistribuicaodfe');
    }

}