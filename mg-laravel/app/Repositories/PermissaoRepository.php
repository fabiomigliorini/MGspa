<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Permissao;
use App\Models\GrupoUsuarioPermissao;
use App\Models\GrupoUsuario;
use Auth;
use Route;

/**
 * Description of PermissaoRepository
 *
 */
class PermissaoRepository extends MGRepositoryStatic
{
    public static $modelClass = '\\App\\Models\\Permissao';

    public static function getValidationRules($model = null)
    {
        return [
            'permissao' => [
                'required',
                Rule::unique('tblpermissao')->ignore($model->codpermissao, 'codpermissao')
            ]
        ];
    }

    public static function validationMessages($model = null)
    {
        return [
            'permissao.required' => 'O campo Permissao não pode ser vazio',
            'permissao.unique' => 'Esta Permissao já esta cadastrada',
        ];
    }

    public static function authorize($codfilial = null, $codusuario = null, $rota = null)
    {

        // Descobre o Codigo do Usuario
        if (empty($codusuario)) {
            $codusuario = Auth::user()->codusuario;
        }

        // Descobre a Rota
        if (empty($rota)) {
            $rota = Route::currentRouteName();
        }

        // Se tem mais filiais para autorizar percorre as filiais, chamando o metodo para cada filial
        if (is_array($codfilial)) {
            foreach ($codfilial as $cod) {
                if (!static::permitido($cod, $codusuario, $rota)) {
                    return false;
                }
            }
            return true;
        }

        // Monta Consulta ao Banco
        $query = Permissao::where('permissao', $rota);
        $query->join('tblgrupousuariopermissao', 'tblgrupousuariopermissao.codpermissao', '=', 'tblpermissao.codpermissao')
            ->join('tblgrupousuariousuario', 'tblgrupousuariousuario.codgrupousuario', '=', 'tblgrupousuariopermissao.codgrupousuario')
            ->where('tblgrupousuariousuario.codusuario', $codusuario)
            ;

        // Se eh pra verificar a filial tambem
        if (!empty($codfilial)) {
            $query->where('tblgrupousuariousuario.codfilial', $codfilial);
        }

        // Se veio registro e porque esta autorizado
        $count = $query->count();
        return $count > 0;
    }

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
            $ret[$grupo][$nome]['codpermissao'] = null;
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

    public static function adicionaPermissao($rota, $codgrupousuario)
    {
        if (!$permissao = Permissao::where('permissao', $rota)->first()) {
            $permissao = static::create(null, ['permissao' => $rota]);
        }
        if (!GrupoUsuarioPermissao::where('codgrupousuario', $codgrupousuario)->where('codpermissao', $permissao->codpermissao)->first()) {
            if (!$gup = GrupoUsuarioPermissao::create([
                'codgrupousuario' => $codgrupousuario,
                'codpermissao' => $permissao->codpermissao,
            ])) {
                return false;
            }
        }
        return true;
    }

    public static function removePermissao($rota, $codgrupousuario)
    {
        if (!$permissao = Permissao::where('permissao', $rota)->first()) {
            return true;
        }
        if (!$gup = GrupoUsuarioPermissao::where('codgrupousuario', $codgrupousuario)->where('codpermissao', $permissao->codpermissao)->first()) {
            return true;
        }
        return $gup->delete();
    }
}
