<?php

namespace Usuario;

class GrupoUsuarioRepository
{
    public static function details ($id) {

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

}
