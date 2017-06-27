<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\EstadoCivil;

/**
 * Description of EstadoCivilRepository
 * 
 * @property  Validator $validator
 * @property  EstadoCivil $model
 */
class EstadoCivilRepository extends MGRepository {
    
    public function boot() {
        $this->model = new EstadoCivil();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codestadocivil;
        }
        
        $this->validator = Validator::make($data, [
            'estadocivil' => [
                'max:50',
                'required',
            ],
        ], [
            'estadocivil.max' => 'O campo "estadocivil" não pode conter mais que 50 caracteres!',
            'estadocivil.required' => 'O campo "estadocivil" deve ser preenchido!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->PessoaS->count() > 0) {
            return 'Estado Civil sendo utilizada em "Pessoa"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = EstadoCivil::query();
        
        // Filtros
         if (!empty($filters['codestadocivil'])) {
            $qry->where('codestadocivil', '=', $filters['codestadocivil']);
        }

         if (!empty($filters['estadocivil'])) {
            $qry->palavras('estadocivil', $filters['estadocivil']);
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
            , 'recordsTotal' => EstadoCivil::count()
            , 'data' => $qry->get()
        ];
        
    }
    
}
