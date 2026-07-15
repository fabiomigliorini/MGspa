<?php

namespace Mg\Classificacao;

use Mg\MgModel;

/**
 * Valor de um parâmetro dentro de uma tabela de classificação (N:N). Guarda o
 * "quanto" (ordem da cascata + tolerância + fator/deságio) para aquele parâmetro
 * naquela tabela. O "como calcular" (metodo/reduzbase) vem do catálogo
 * (ParametroClassificacao).
 */
class TabelaClassificacaoItem extends MgModel
{
    protected $table = 'tbltabelaclassificacaoitem';
    protected $primaryKey = 'codtabelaclassificacaoitem';

    protected $fillable = [
        'codtabelaclassificacao',
        'codparametroclassificacao',
        'ordem',
        'tolerancia',
        'fator',
        'desagio',
    ];

    protected $casts = [
        'alteracao' => 'datetime',
        'codparametroclassificacao' => 'integer',
        'codtabelaclassificacao' => 'integer',
        'codtabelaclassificacaoitem' => 'integer',
        'codusuarioalteracao' => 'integer',
        'codusuariocriacao' => 'integer',
        'criacao' => 'datetime',
        'desagio' => 'float',
        'fator' => 'float',
        'ordem' => 'integer',
        'tolerancia' => 'float',
    ];

    // Chaves Estrangeiras
    public function ParametroClassificacao()
    {
        return $this->belongsTo(ParametroClassificacao::class, 'codparametroclassificacao', 'codparametroclassificacao');
    }

    public function TabelaClassificacao()
    {
        return $this->belongsTo(TabelaClassificacao::class, 'codtabelaclassificacao', 'codtabelaclassificacao');
    }
}
