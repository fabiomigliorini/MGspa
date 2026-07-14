<?php

namespace Mg\Classificacao;

use Mg\MgModel;
use Mg\Cultura\Cultura;

/**
 * Tabela (padrão) de classificação nomeada, por cultura. Ex.: "Padrão Milho",
 * "Milho-Bunge", "Soja-Aprosoja". Os valores por parâmetro (ordem/tolerância/
 * fator/deságio) ficam na filha tbltabelaclassificacaoitem. A cultura aponta a
 * sua tabela padrão (tblcultura.codtabelaclassificacao); contrato e carga também
 * apontam a tabela usada — a resolução vive no CargaService.
 */
class TabelaClassificacao extends MgModel
{
    protected $table = 'tbltabelaclassificacao';
    protected $primaryKey = 'codtabelaclassificacao';

    protected $fillable = [
        'codcultura',
        'tabelaclassificacao',
        'inativo',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codcultura' => 'integer',
        'codtabelaclassificacao' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'inativo' => 'datetime',
    ];

    // Chaves Estrangeiras
    public function Cultura()
    {
        return $this->belongsTo(Cultura::class, 'codcultura', 'codcultura');
    }

    // Tabelas Filhas
    public function TabelaClassificacaoItemS()
    {
        return $this->hasMany(TabelaClassificacaoItem::class, 'codtabelaclassificacao', 'codtabelaclassificacao');
    }
}
