<?php

namespace Mg\Usuario;

use Mg\MgRepository;

class GrupoUsuarioRepository extends MgRepository
{
    public static function detalhes($id) {

        $model = GrupoUsuario::findOrFail($id);
        $usuarios = [];
        foreach ($model->GrupoUsuarioUsuarioS as $usuario) {

            foreach ($usuario->Usuario->GrupoUsuarioUsuarioS as $grupo) {
                $grupos[$grupo->GrupoUsuario->codgrupousuario] = $grupo->GrupoUsuario->grupousuario;
            }

            $usuarios[$usuario['codusuario']] = [
                'codusuario' => $usuario->Usuario->codusuario,
                'usuario' => $usuario->Usuario->usuario,
                'grupos' => $grupos
            ];
        }

        $model['Usuarios'] = $usuarios;
        $model['usuariocriacao'] = $model->UsuarioCriacao->usuario ?? false;
        $model['usuarioalteracao'] = $model->UsuarioAlteracao->usuario ?? false;

        return $model;
    }

    public static function pesquisar(array $filter = null, array $sort = null, array $fields = null)
    {
        $qry = GrupoUsuario::query();

        if (!empty($filter['inativo'])) {
            $qry->AtivoInativo($filter['inativo']);
        }

        if (!empty($filter['usuario'])) {
            $qry->palavras('usuario', $filter['usuario']);
        }

        $qry = self::qryOrdem($qry, $sort);
        $qry = self::qryColunas($qry, $fields);
        return $qry;
    }

}
