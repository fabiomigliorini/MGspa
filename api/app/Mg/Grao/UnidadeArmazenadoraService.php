<?php

namespace Mg\Grao;

use Mg\MgService;

class UnidadeArmazenadoraService extends MgService
{
    const WITH = ['Pessoa'];

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
    {
        $qry = UnidadeArmazenadora::query()->with(static::WITH);

        if (!empty($filter['codunidadearmazenadora'])) {
            $qry->where('codunidadearmazenadora', $filter['codunidadearmazenadora']);
        }
        if (!empty($filter['tipo'])) {
            $qry->where('tipo', $filter['tipo']);
        }
        if (!empty($filter['unidadearmazenadora'])) {
            $qry->palavras('unidadearmazenadora', $filter['unidadearmazenadora']);
        }
        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        $qry = self::qryOrdem($qry, $sort ?: ['unidadearmazenadora']);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

    public static function salvar(array $data, ?UnidadeArmazenadora $model = null): UnidadeArmazenadora
    {
        $model = $model ?: new UnidadeArmazenadora();
        $model->fill($data);
        $model->save();
        return $model->fresh(static::WITH);
    }
}
