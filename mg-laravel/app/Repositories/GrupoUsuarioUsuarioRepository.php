<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;


use App\Models\GrupoUsuarioUsuario;

/**
 * Description of GrupoUsuarioUsuarioRepository
 * 
 * @property Validator $validator
 * @property GrupoUsuarioUsuario $model
 */
class GrupoUsuarioUsuarioRepository extends MGRepository {
    
    public function boot() {
        $this->model = new GrupoUsuarioUsuario();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $model->getAttributes();
        }
        
        if (empty($id)) {
            $id = $model->codgrupousuariousuario;
        }
        
        $this->validator = Validator::make($data, [
            'grupousuario' => [
                'required',
                'min:5',
                Rule::unique('tblgrupousuario')->ignore($id, 'codgrupousuario')
            ],            
        ], [
            'grupousuario.required' => 'O campo Grupo Usuário não pode ser vazio!',
            'grupousuario.min' => 'O campo Grupo Usuário deve ter mais de 3 caracteres!',
            'grupousuario.unique' => 'Esta Grupo de Usuário já esta cadastrado!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        //...
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        /*
        // Query da Entidade
        $qry = GrupoUsuarioUsuario::query();
        
        // Filtros
        if (!empty($filters['codgrupousuario'])) {
            $qry->where('codgrupousuario', '=', $filters['codgrupousuario']);
        }
        
        if (!empty($filters['grupousuario'])) {
            foreach(explode(' ', $filters['grupousuario']) as $palavra) {
                if (!empty($palavra)) {
                    $qry->where('grupousuario', 'ilike', "%$palavra%");
                }
            }
        }
        
        if (!empty($filters['sigla'])) {
            foreach(explode(' ', $filters['sigla']) as $palavra) {
                if (!empty($palavra)) {
                    $qry->where('sigla', 'ilike', "%$palavra%");
                }
            }
        }
        
        switch ($filters['inativo']) {
            case 2: //Inativos
                $qry = $qry->inativo();
                break;

            case 9: //Todos
                break;

            case 1: //Ativos
            default:
                $qry = $qry->ativo();
                break;
        }
        
        // Paginacao
        if (!empty($start)) {
            $qry->offset($start);
        }
        if (!empty($length)) {
            $qry->limit($length);
        }
        
        // Ordenacao
        foreach ($sort as $s) {
            $qry->orderBy($s['column'], $s['dir']);
        }
        
        // Registros
        return $qry->get();
        */
    }
    
}
