<?php

namespace Mg\Classificacao;

use Mg\MgService;
use Mg\Cultura\Cultura;
use Illuminate\Support\Facades\DB;

class TabelaClassificacaoService extends MgService
{
    const WITH = ['Cultura', 'TabelaClassificacaoItemS.ParametroClassificacao'];

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = TabelaClassificacao::query()->with(static::WITH);

        if (!empty($filter['codtabelaclassificacao'])) {
            $qry->where('codtabelaclassificacao', $filter['codtabelaclassificacao']);
        }

        if (!empty($filter['codcultura'])) {
            $qry->where('codcultura', $filter['codcultura']);
        }

        if (!empty($filter['tabelaclassificacao'])) {
            $qry->palavras('tabelaclassificacao', $filter['tabelaclassificacao']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['tabelaclassificacao']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    /** Salva a tabela + seus itens (delete+recreate) numa transação. */
    public static function salvar(array $dados, ?TabelaClassificacao $model = null): TabelaClassificacao
    {
        return DB::transaction(function () use ($dados, $model) {
            $model = $model ?: new TabelaClassificacao();
            $model->fill($dados);
            $model->save();
            static::sincronizarItens($model, $dados['itens'] ?? []);
            return $model->fresh(static::WITH);
        });
    }

    /** Substitui os itens (valores por parâmetro) da tabela pelo conjunto informado. */
    protected static function sincronizarItens(TabelaClassificacao $model, array $itens): void
    {
        TabelaClassificacaoItem::where('codtabelaclassificacao', $model->codtabelaclassificacao)->delete();
        foreach ($itens as $it) {
            $codparam = $it['codparametroclassificacao'] ?? null;
            if (empty($codparam)) {
                continue;
            }
            $item = new TabelaClassificacaoItem();
            $item->codtabelaclassificacao = $model->codtabelaclassificacao;
            $item->codparametroclassificacao = $codparam;
            $item->ordem = $it['ordem'] ?? 0;
            $item->tolerancia = $it['tolerancia'] ?? 0;
            $item->fator = $it['fator'] ?? 0;
            $item->desagio = $it['desagio'] ?? 0;
            $item->save();
        }
    }

    /** Marca esta tabela como a padrão da sua cultura (tblcultura.codtabelaclassificacao). */
    public static function marcarPadrao(TabelaClassificacao $model): TabelaClassificacao
    {
        $cultura = Cultura::find($model->codcultura);
        if ($cultura) {
            $cultura->codtabelaclassificacao = $model->codtabelaclassificacao;
            $cultura->save();
        }
        return $model;
    }
}
