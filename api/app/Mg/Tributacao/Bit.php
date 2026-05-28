<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:44:11
 */

namespace Mg\Tributacao;

use Mg\MgModel;

class Bit extends MgModel
{
    protected $table = 'tblbit';
    protected $primaryKey = 'codbit';


    protected $fillable = [
        'descricao',
        'ncm',
        'numero'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codbit' => 'integer',
        'criacao' => 'datetime',
        'numero' => 'integer'
    ];

}
