<?php

namespace Permissao;

use Usuario\GrupoUsuario;
use Auth;
use Route;

/**
 * Description of PermissaoRepository
 *
 */
class PermissaoRepository
{

    /**
     * retorna listagem das rotas agrupadas
     */
    public static function listagemNomesRotas()
    {
        // Pega todas rotas registradas no Laravel
        $rotas = Route::getRoutes();

        // percorre as rotas ignorando as que nao tem nome
        foreach ($rotas as $rota) {
            $nome = $rota->getName();
            if (!empty($nome)) {
                $nomes[] = $nome;
            }
        }

        // elimina rotas com nomes duplicados
        $nomes = array_unique($nomes);

        // agrupa as rotas pelo primeiro nome
        // ex 'produto.store', o grupo sera 'produto'
        foreach ($nomes as $nome) {
            $arr = explode('.', $nome);
            $grupo = $arr[0]??null;
            $ret[$grupo][$nome]['codpermissao'] = '';
            $ret[$grupo][$nome]['codgrupousuario'] = [];
        }

        // retorna array com as rotas agrupadas
        return $ret;
    }

    /**
     * retorna listagem das rotas combinadas com as permissoes cadastradas no banco
     */
    public static function listagemPermissoes()
    {
        // busca listagem das rotas registradas no laravel
        $rotas = static::listagemNomesRotas();

        // percorre permissoes gravadas no banco
        foreach (Permissao::orderBy('permissao', 'ASC')->get() as $permissao) {

            // agrupa as rotas pelo primeiro nome
            // ex 'produto.store', o grupo sera 'produto'
            $arr = explode('.', $permissao->permissao);
            $grupo = $arr[0]??null;

            // se a rota nao esta registralistagemPermissoesda no Laravel, joga no grupo de 'INATIVOS'
            if (!isset($rotas[$grupo][$permissao->permissao])) {
                $grupo = 'INATIVOS';
            }

            // associa a rota ao codigo gravado no banco de dados
            $rotas[$grupo][$permissao->permissao]['codpermissao'] = $permissao->codpermissao;
            $rotas[$grupo][$permissao->permissao]['codgrupousuario'] = [];
        }

        // retorna as rotas
        return $rotas;
    }

    public static function listagemPermissoesPorGrupoUsuario()
    {
        $permissoes = static::listagemPermissoes();
        $gups = GrupoUsuarioPermissao::orderBy('codpermissao')->with('Permissao')->get();
        foreach ($gups as $gup) {
            // agrupa as rotas pelo primeiro nome
            // ex 'produto.store', o grupo sera 'produto'
            $arr = explode('.', $gup->Permissao->permissao);
            $grupo = $arr[0]??null;

            // se a rota nao esta registralistagemPermissoesda no Laravel, joga no grupo de 'INATIVOS'
            if (!isset($permissoes[$grupo][$gup->Permissao->permissao])) {
                $grupo = 'INATIVOS';
            }

            // associa a rota ao codigo gravado no banco de dados
            $permissoes[$grupo][$gup->Permissao->permissao]['codgrupousuario'][] = $gup->codgrupousuario;
        }

        return [
            'Grupos' => GrupoUsuario::orderBy('codgrupousuario')->get(),
            'Permissoes' => $permissoes
        ];
    }

}
