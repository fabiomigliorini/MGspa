<?php
/**
 * Created by php artisan gerador:model.
 * Date: 27/May/2026 11:40:36
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

    protected $casts = [
        'alteracao' => 'datetime',
        'codantigo' => 'integer',
        'codauditoria' => 'integer',
        'codnovo' => 'integer',
        'criacao' => 'datetime',
        'data' => 'datetime'
    ];


    // Tabelas Filhas
    public function AuditoriaTransmissaoS()
    {
        return $this->hasMany(AuditoriaTransmissao::class, 'codauditoria', 'codauditoria');
    }

}
