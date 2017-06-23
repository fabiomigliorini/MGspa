<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\Cest;

/**
 * Description of CestRepository
 * 
 * @property  Validator $validator
 * @property  Cest $model
 */
class CestRepository extends MGRepository {
    
    public function boot() {
        $this->model = new Cest();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codcest;
        }
        
        $this->validator = Validator::make($data, [
            'cest' => [
                'max:7',
                'required',
            ],
            'ncm' => [
                'max:8',
                'required',
            ],
            'descricao' => [
                'max:600',
                'required',
            ],
            'codncm' => [
                'numeric',
                'nullable',
            ],
        ], [
            'cest.max' => 'O campo "cest" não pode conter mais que 7 caracteres!',
            'cest.required' => 'O campo "cest" deve ser preenchido!',
            'ncm.max' => 'O campo "ncm" não pode conter mais que 8 caracteres!',
            'ncm.required' => 'O campo "ncm" deve ser preenchido!',
            'descricao.max' => 'O campo "descricao" não pode conter mais que 600 caracteres!',
            'descricao.required' => 'O campo "descricao" deve ser preenchido!',
            'codncm.numeric' => 'O campo "codncm" deve ser um número!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->ProdutoS->count() > 0) {
            return 'Cest sendo utilizada em "Produto"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = Cest::query();
        
        // Filtros
         if (!empty($filters['codcest'])) {
            $qry->where('codcest', '=', $filters['codcest']);
        }

         if (!empty($filters['cest'])) {
            $qry->palavras('cest', $filters['cest']);
        }

         if (!empty($filters['ncm'])) {
            $qry->palavras('ncm', $filters['ncm']);
        }

         if (!empty($filters['descricao'])) {
            $qry->palavras('descricao', $filters['descricao']);
        }

         if (!empty($filters['alteracao'])) {
            $qry->where('alteracao', '=', $filters['alteracao']);
        }

         if (!empty($filters['codusuarioalteracao'])) {
            $qry->where('codusuarioalteracao', '=', $filters['codusuarioalteracao']);
        }

         if (!empty($filters['criacao'])) {
            $qry->where('criacao', '=', $filters['criacao']);
        }

         if (!empty($filters['codusuariocriacao'])) {
            $qry->where('codusuariocriacao', '=', $filters['codusuariocriacao']);
        }

         if (!empty($filters['codncm'])) {
            $qry->where('codncm', '=', $filters['codncm']);
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
            , 'recordsTotal' => Cest::count()
            , 'data' => $qry->get()
        ];
        
    }
    
}
