<?php
/**
 * Created by php artisan gerador:model.
 * Date: 29/Oct/2024 12:28:20
 */

namespace Mg\Filial;

use Mg\MgModel;
use Mg\Filial\AuditoriaTransmissao;

class Auditoria extends MgModel
{
    protected $table = 'tblauditoria';
    protected $primaryKey = 'codauditoria';


    protected $fillable = [
        'antigo',
        'codantigo',
        'codnovo',
        'colunaalterada',
        'colunacodigo',
        'data',
        'novo',
        'operacao',
        'tabela'
    ];

    protected $dates = [
        'data'
    ];

    protected $casts = [
        'codantigo' => 'integer',
        'codauditoria' => 'integer',
        'codnovo' => 'integer'
    ];


    // Tabelas Filhas
    public function AuditoriaTransmissaoS()
    {
        return $this->hasMany(AuditoriaTransmissao::class, 'codauditoria', 'codauditoria');
    }

}