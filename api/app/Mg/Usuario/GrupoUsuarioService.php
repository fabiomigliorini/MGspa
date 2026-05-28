<?php

namespace Mg\Usuario;

use Mg\MgService;

class GrupoUsuarioService extends MgService
{
    public static function detalhes($id): GrupoUsuario
    {
        return GrupoUsuario::findOrFail($id);
    }

    public static function pesquisar(?array $filter = null, ?array $sort = null, ?array $fields = null)
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
