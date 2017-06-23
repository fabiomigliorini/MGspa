<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\GrupoUsuario;

/**
 * Description of GrupoUsuarioRepository
 * 
 * @property Validator $validator
 * @property GrupoUsuario $model
 */
class GrupoUsuarioRepository extends MGRepository {
    
    public function boot() {
        $this->model = new GrupoUsuario();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $this->model->getAttributes();
        }
        
        if (empty($id)) {
            $this->model->codgrupousuario;
        }
        
        $this->validator = Validator::make($data, [
            'grupousuario' => [
                'required',
                Rule::unique('tblgrupousuario')->ignore($id, 'codgrupousuario')
            ],            
        ], [
            'grupousuario.required' => 'O campo Grupo Usuário não pode ser vazio',
            'grupousuario.unique' => 'Esta Descrição já esta cadastrada',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        if ($this->model->GrupoUsuarioPermissaoS->count() > 0) {
            return 'Grupo de usuário sendo utilizada em Permissões!';
        }
        if ($this->model->GrupoUsuarioUsuarioS->count() > 0) {
            return 'Grupo de usuário sendo utilizada em Usuarios!';
        }
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = GrupoUsuario::query();
        
        // Filtros
        if (!empty($filters['codgrupousuario'])) {
            $qry->where('codgrupousuario', '=', $filters['codgrupousuario']);
        }
        
        if (!empty($filters['grupousuario'])) {
            $qry->palavras('grupousuario', $filters['grupousuario']);
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
        
        $count = $qry->count();
        
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
        return [
            'recordsFiltered' => $count
            , 'recordsTotal' => GrupoUsuario::count()
            , 'data' => $qry->get()
        ];        
    }
}
