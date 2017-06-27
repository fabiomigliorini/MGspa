<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Permissao;

/**
 * Description of PermissaoRepository
 *
 */
class PermissaoRepository extends MGRepositoryStatic
{
    public static $modelClass = 'Permissao';

    public static function validate($model = null, array $data = null, &$errors)
    {
        if (empty($data)) {
            if (empty($model)) {
                return false;
            }
            $data = $model->getAttributes();
        }

        $id = $data['codpermissao']??$model->codpermissao??null;

        $validator = Validator::make($data, [
            'permissao' => [
                'required',
                Rule::unique('tblpermissao')->ignore($id, 'codpermissao')
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
}
