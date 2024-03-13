<?php

namespace Mg\Usuario;

use Illuminate\Support\Facades\DB;
use Mg\MgService;
use stdClass;

class GrupoUsuarioService extends MgService
{
    public static function detalhes($id)
    {

        $model = GrupoUsuario::findOrFail($id);
        return $model;
    }

    public static function pesquisar(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = GrupoUsuario::query();

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['grupo'])) {
            $qry->palavras('grupousuario', $filter['grupo']);
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

}
