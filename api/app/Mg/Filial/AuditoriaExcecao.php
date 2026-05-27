<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:44:05
 */

namespace Mg\Filial;

use Mg\MgModel;

class AuditoriaExcecao extends MgModel
{
    protected $table = 'tblauditoriaexcecao';
    protected $primaryKey = 'codauditoriaexcecao';


    protected $fillable = [
        'campo',
        'tabela'
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codauditoriaexcecao' => 'integer',
        'criacao' => 'datetime'
    ];

}
