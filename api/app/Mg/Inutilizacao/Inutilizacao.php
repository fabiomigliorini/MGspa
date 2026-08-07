<?php

namespace Mg\Inutilizacao;

use Mg\MgModel;
use Mg\Filial\Filial;

/**
 * Inutilizacao de uma FAIXA de numeracao na SEFAZ.
 *
 * Nao tem FK para tblnotafiscal de proposito: a faixa pode cobrir numeros que nunca
 * tiveram nota (que e o caso tipico — lacuna de numeracao). Antes disso o sistema criava
 * uma tblnotafiscal falsa so para carregar o numero.
 */
class Inutilizacao extends MgModel
{
    protected $table = 'tblinutilizacao';
    protected $primaryKey = 'codinutilizacao';

    protected $fillable = [
        'codfilial',
        'modelo',
        'serie',
        'numeroinicial',
        'numerofinal',
        'ambiente',
        'justificativa',
        'protocolo',
        'protocolodata',
        'cstat',
        'xmotivo',
        'arquivo',
    ];

    protected $casts = [
        'codinutilizacao' => 'integer',
        'codfilial' => 'integer',
        'modelo' => 'integer',
        'serie' => 'integer',
        'numeroinicial' => 'integer',
        'numerofinal' => 'integer',
        'ambiente' => 'integer',
        'protocolodata' => 'datetime',
        'criacao' => 'datetime',
        'alteracao' => 'datetime',
        'codusuariocriacao' => 'integer',
        'codusuarioalteracao' => 'integer',
    ];

    public function Filial()
    {
        return $this->belongsTo(Filial::class, 'codfilial', 'codfilial');
    }

    /** Quantidade de numeros cobertos pela faixa. */
    public function getQuantidadeAttribute(): int
    {
        return ($this->numerofinal - $this->numeroinicial) + 1;
    }

    public function getHomologadaAttribute(): bool
    {
        return !empty($this->protocolo);
    }
}
