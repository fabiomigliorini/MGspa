<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Permissao;
use Auth;
use Route;

/**
 * Description of PermissaoRepository
 *
 */
class PermissaoRepository extends MGRepositoryStatic
{
    public static $modelClass = 'Permissao';

    public static function validate($model = null, &$errors)
    {
        $data = $model->getAttributes();

        $validator = Validator::make($data, [
            'permissao' => [
                'required',
                Rule::unique('tblpermissao')->ignore($model->codpermissao, 'codpermissao')
            ],
        ], [
            'permissao.required' => 'O campo Permissao não pode ser vazio',
            'permissao.unique' => 'Esta Permissao já esta cadastrada',
        ]);

        if (!$validator->passes()) {
            $errors = $validator->errors()->all();
            return false;
        }

        return true;
    }

    public static function used($model)
    {
        if ($model->GrupoUsuario->count() > 0) {
            return 'Permissão já anexada para um Grupo de Usuários!';
        }
        return false;
    }

    public static function permissaoClasses()
    {
        // Pega todos arquivos da pasta de Policies
        $arquivos = scandir(base_path() . '/app/Policies/');

        // Percorre arquivos para buscar metodos
        $classes = [];
        foreach ($arquivos as $arquivo) {

            // Ignora arquivos
            if (in_array($arquivo, ['.', '..', 'MGPolicy.php'])) {
                continue;
            }

            if (strstr($arquivo, '.old.')) {
                continue;
            }

            // Tira a extensao do arquivo
            $classe = str_replace('.php', '', $arquivo);

            // Pega metodos da Classe
            $metodos = get_class_methods("App\\Policies\\{$classe}");

            // Metodos para serem ignorados
            $metodos = array_diff($metodos, ['before']);

            $metodos_como_chave = [];
            foreach ($metodos as $metodo) {
                $metodos_como_chave[$metodo] = 0;
            }

            // Acumula na listagem de classes
            $classes[$classe] = $metodos_como_chave;
        }

        // Percorre Permissoes combinando com as classes
        foreach (Permissao::orderBy('permissao')->get() as $permissao) {
            $classe = null;
            $metodo = null;

            // Tenta separar do formato 'Classe.metodo'
            if ($ponto = strpos($permissao->permissao, '.')) {
                $classe = substr($permissao->permissao, 0, $ponto);
                $metodo = substr($permissao->permissao, $ponto + 1);
            }

            // Se existe uma permissao no banco, associa o codigo
            if (isset($classes[$classe][$metodo])) {
                $classes[$classe][$metodo] = $permissao->codpermissao;

            // Senao Lista como obsoleta
            } else {
                $classes['OBSOLETAS'][$permissao->permissao] = $permissao->codpermissao;
            }
        }

        return $classes;
    }

    public static function permissaoGrupos()
    {
        // Listagem dos Grupos
        $grupos = \App\Models\GrupoUsuario::ativo()->orderBy('grupousuario')->get();

        // Listagem das Permissoes dos Grupos
        $grupopermissoes = \App\Models\GrupoUsuarioPermissao::select('codgrupousuariopermissao', 'codgrupousuario', 'codpermissao')->get();
        $permissoes = [];
        foreach ($grupopermissoes as $grupopermissao) {
            $permissoes[$grupopermissao->codgrupousuario][$grupopermissao->codpermissao] = $grupopermissao->codgrupousuariopermissao;
        }
        return $permissoes;
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
            $ret[$grupo][$nome] = null;
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

            // se a rota nao esta registrada no Laravel, joga no grupo de 'INATIVOS'
            if (!isset($rotas[$grupo][$permissao->permissao])) {
                $grupo = 'INATIVOS';
            }

            // associa a rota ao codigo gravado no banco de dados
            $rotas[$grupo][$permissao->permissao] = $permissao->codpermissao;

        }

        // retorna as rotas
        return $rotas;
    }

    public static function adicionaPermissao ($rota, $codgrupousuario)
    {
        if (!$permissao = Permissao::where('permissao', $rota)->first()) {
            $permissao = static::create(null, ['permissao' => $rota]);
        }
        if (!GrupoUsuarioPermissao::where('codgrupousuario', $codgrupousuario)->where('codpermissao', $permissao->codpermissao)->first()) {
            if (!$gup = GrupoUsuarioPermissao::create([
                'codgrupousuario', $codgrupousuario,
                'codpermissao', $permissao->codpermissao,
            ])) {
                return false;
            }
        }
        return true;
    }

    public static function removePermissao ($rota, $codgrupousuario)
    {
        if (!$permissao = Permissao::where('permissao', $rota)->first()) {
            return true;
        }
        if (!$gup = GrupoUsuarioPermissao::where('codgrupousuario', $codgrupousuario)->where('codpermissao', $permissao->codpermissao)->first()) {
            if (!$gup->delete()) {
                return false;
            }
        }
        return true;
    }
}
