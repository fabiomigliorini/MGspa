<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Usuario;
use App\Models\Permissao;

/**
 * Description of UsuarioRepository
 *
 * @property Validator $validator
 * @property Usuario $model
 */
class UsuarioRepository extends MGRepositoryStatic
{
    const PERMISSAO_CONSULTA = 100;
    const PERMISSAO_MANUTENCAO = 200;
    const PERMISSAO_ADMINISTRACAO = 300;

    public static $modelClass = 'Usuario';

    public static function permitido (
        Usuario $usuario,
        $permissao,
        $nivel = self::PERMISSAO_CONSULTA,
        $codfilial = null,
        $codfilial_anterior = null)
    {

        if (!empty($codfilial_anterior)) {
            if (!static::permitido($usuario, $permissao, $nivel, $codfilial_anterior)) {
                return false;
            }
        }

        $query = Permissao::where('permissao', $permissao);

        $query->join('tblgrupousuariopermissao', 'tblgrupousuariopermissao.codpermissao', '=', 'tblpermissao.codpermissao')
            ->join('tblgrupousuariousuario', 'tblgrupousuariousuario.codgrupousuario', '=', 'tblgrupousuariopermissao.codgrupousuario')
            ->where('tblgrupousuariousuario.codusuario', $usuario->codusuario)
            ->where('tblgrupousuariopermissao.nivel', '>=', $nivel)
            ;

        if (!empty($codfilial)) {
            $query->where('tblgrupousuariousuario.codfilial', $codfilial);
        }

        $count = $query->count();

        return true;
        return $count > 0;

    }
}
