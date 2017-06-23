<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;


use App\Models\Permissao;

/**
 * Description of PermissaoRepository
 * 
 * @property Validator $validator
 * @property Permissao $model
 */
class PermissaoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new Permissao();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $model->getAttributes();
        }
        
        if (empty($id)) {
            $id = $model->codpermissao;
        }
        
        $this->validator = Validator::make($data, [
            // ...
        ], [
            //...
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        if ($this->model->GrupoUsuario->count() > 0) {
            return 'Permissão sendo utilizada em Grupo Usuario!';
        }
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
    }
    
}
