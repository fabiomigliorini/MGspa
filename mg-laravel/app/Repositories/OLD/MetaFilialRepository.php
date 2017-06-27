<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\MetaFilial;

/**
 * Description of MetaFilialRepository
 * 
 * @property  Validator $validator
 * @property  MetaFilial $model
 */
class MetaFilialRepository extends MGRepository {
    
    public function boot() {
        $this->model = new MetaFilial();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codmetafilialpessoa;
        }
        
        $this->validator = Validator::make($data, [
            'codmetafilial' => [
                'numeric',
                'required',
            ],
            'codpessoa' => [
                'numeric',
                'required',
            ],
            'codcargo' => [
                'numeric',
                'required',
            ],
        ], [
            'codmetafilial.numeric' => 'O campo "codmetafilial" deve ser um número!',
            'codmetafilial.required' => 'O campo "codmetafilial" deve ser preenchido!',
            'codpessoa.numeric' => 'O campo "codpessoa" deve ser um número!',
            'codpessoa.required' => 'O campo "codpessoa" deve ser preenchido!',
            'codcargo.numeric' => 'O campo "codcargo" deve ser um número!',
            'codcargo.required' => 'O campo "codcargo" deve ser preenchido!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = MetaFilial::query();
        
        // Filtros
         if (!empty($filters['codmetafilialpessoa'])) {
            $qry->where('codmetafilialpessoa', '=', $filters['codmetafilialpessoa']);
        }

         if (!empty($filters['codmetafilial'])) {
            $qry->where('codmetafilial', '=', $filters['codmetafilial']);
        }

         if (!empty($filters['codpessoa'])) {
            $qry->where('codpessoa', '=', $filters['codpessoa']);
        }

         if (!empty($filters['codcargo'])) {
            $qry->where('codcargo', '=', $filters['codcargo']);
        }
        
        $count = $qry->count();
    
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
        return [
            'recordsFiltered' => $count
            , 'recordsTotal' => MetaFilial::count()
            , 'data' => $qry->get()
        ];
        
    }
    
}
