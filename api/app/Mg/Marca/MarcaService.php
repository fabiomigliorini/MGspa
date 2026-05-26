<?php

namespace Mg\Marca;

use Mg\MgService;

class MarcaService extends MgService
{
    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = Marca::query();

        if (!empty($filter['codmarca'])) {
            $qry->where('codmarca', $filter['codmarca']);
        }

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['marca'])) {
            $qry->palavras('marca', $filter['marca']);
        }

        if (filter_var($filter['sobrando'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('itensacimamaximo', '>', 0);
        }

        if (filter_var($filter['faltando'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $qry->where('itensabaixominimo', '>', 0);
        }

        if (!empty($filter['abccategoria']['min'])) {
            $qry->where('abccategoria', '>=', $filter['abccategoria']['min']);
        }

        if (!empty($filter['abccategoria']['max'])) {
            $qry->where('abccategoria', '<=', $filter['abccategoria']['max']);
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function autocompletar(array $params): array
    {
        $qry = static::pesquisar($params)
            ->select('codmarca', 'marca')
            ->take(20);
        $ret = [];
        foreach ($qry->get() as $item) {
            $ret[] = [
                'label' => $item->marca,
                'value' => $item->marca,
                'id' => $item->codmarca,
            ];
        }
        return $ret;
    }

    public static function detalhes(int $id): Marca
    {
        $model = Marca::findOrFail($id);
        if (!empty($model->codimagem)) {
            $model->imagem = $model->Imagem;
            $model->imagem->url = $model->Imagem->url;
        }
        $model->produtosAbaixoMinimo = [];
        $model->produtosAcimaMaximo = [];
        return $model;
    }
}
