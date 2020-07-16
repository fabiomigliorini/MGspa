<?php

namespace Mg\NFePHP;

use Mg\MgModel;
use Mg\Filial\Filial;
use Mg\Estado\Estado;

class IbptCache extends MGModel
{
    protected $table = 'tblibptcache';
    protected $primaryKey = 'codibptcache';
    protected $fillable = [
        'codfilial',
        'codestado',
        'ncm',
        'extarif',
        'descricao',
        'nacional',
        'estadual',
        'importado',
        'municipal',
        'tipo',
        'vigenciainicio',
        'vigenciafim',
        'chave',
        'versao',
        'fonte',
    ];
    protected $dates = [
        'vigenciainicio',
        'vigenciafim',
    ];

    // Chaves Estrangeiras
    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    public function Estado()
    {
        return $this->belongsTo(Filial::class, 'codestado', 'codestado');
    }
}
