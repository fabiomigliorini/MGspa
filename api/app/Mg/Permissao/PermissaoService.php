<?php

namespace Mg\Permissao;

use Illuminate\Support\Facades\Route;
use Mg\Usuario\GrupoUsuario;

class PermissaoService
{
    public static function listagemNomesRotas(): array
    {
        $nomes = [];
        foreach (Route::getRoutes() as $rota) {
            $nome = $rota->getName();
            if (!empty($nome)) {
                $nomes[] = $nome;
            }
        }
        $nomes = array_unique($nomes);

        $ret = [];
        foreach ($nomes as $nome) {
            $arr = explode('.', $nome);
            $grupo = $arr[0] ?? null;
            $ret[$grupo][$nome]['codpermissao'] = '';
            $ret[$grupo][$nome]['codgrupousuario'] = [];
        }

        return $ret;
    }

    public static function listagemPermissoes(): array
    {
        $rotas = static::listagemNomesRotas();

        foreach (Permissao::orderBy('permissao', 'ASC')->get() as $permissao) {
            $arr = explode('.', $permissao->permissao);
            $grupo = $arr[0] ?? null;

            if (!isset($rotas[$grupo][$permissao->permissao])) {
                $grupo = 'INATIVOS';
            }

            $rotas[$grupo][$permissao->permissao]['codpermissao'] = $permissao->codpermissao;
            $rotas[$grupo][$permissao->permissao]['codgrupousuario'] = [];
        }

        return $rotas;
    }

    public static function listagemPermissoesPorGrupoUsuario(): array
    {
        $permissoes = static::listagemPermissoes();
        $gups = GrupoUsuarioPermissao::orderBy('codpermissao')->with('Permissao')->get();

        foreach ($gups as $gup) {
            $arr = explode('.', $gup->Permissao->permissao);
            $grupo = $arr[0] ?? null;

            if (!isset($permissoes[$grupo][$gup->Permissao->permissao])) {
                $grupo = 'INATIVOS';
            }

            $permissoes[$grupo][$gup->Permissao->permissao]['codgrupousuario'][] = $gup->codgrupousuario;
        }

        return [
            'Grupos' => GrupoUsuario::orderBy('codgrupousuario')->get(),
            'Permissoes' => $permissoes,
        ];
    }
}
