<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\Cargo;

/**
 * Description of CargoRepository
 * 
 * @property  Validator $validator
 * @property  Cargo $model
 */
class CargoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new Cargo();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codcargo;
        }
        
        $this->validator = Validator::make($data, [
            'cargo' => [
                'max:50',
                'required',
            ],
        ], [
            'cargo.max' => 'O campo "cargo" não pode conter mais que 50 caracteres!',
            'cargo.required' => 'O campo "cargo" deve ser preenchido!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->MetaFilialPessoaS->count() > 0) {
            return 'Cargo sendo utilizada em "MetaFilialPessoa"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = Cargo::query();
        
        // Filtros
         if (!empty($filters['codcargo'])) {
            $qry->where('codcargo', '=', $filters['codcargo']);
        }

         if (!empty($filters['cargo'])) {
            $qry->palavras('cargo', $filters['cargo']);
        }

          if (!empty($filters['criacao'])) {
            $qry->where('criacao', '=', $filters['criacao']);
        }

         if (!empty($filters['alteracao'])) {
            $qry->where('alteracao', '=', $filters['alteracao']);
        }

         if (!empty($filters['codusuarioalteracao'])) {
            $qry->where('codusuarioalteracao', '=', $filters['codusuarioalteracao']);
        }

         if (!empty($filters['codusuariocriacao'])) {
            $qry->where('codusuariocriacao', '=', $filters['codusuariocriacao']);
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
            , 'recordsTotal' => Cargo::count()
            , 'data' => $qry->get()
        ];        
    }
    
}
