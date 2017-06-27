<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\ChequeEmitente;

/**
 * Description of ChequeEmitenteRepository
 * 
 * @property  Validator $validator
 * @property  ChequeEmitente $model
 */
class ChequeEmitenteRepository extends MGRepository {
    
    public function boot() {
        $this->model = new ChequeEmitente();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codchequeemitente;
        }
        
        $this->validator = Validator::make($data, [
            'codcheque' => [
                'numeric',
                'required',
            ],
            'cnpj' => [
                'numeric',
                'required',
            ],
            'emitente' => [
                'max:100',
                'required',
            ],
        ], [
            'codcheque.numeric' => 'O campo "codcheque" deve ser um número!',
            'codcheque.required' => 'O campo "codcheque" deve ser preenchido!',
            'cnpj.numeric' => 'O campo "cnpj" deve ser um número!',
            'cnpj.required' => 'O campo "cnpj" deve ser preenchido!',
            'emitente.max' => 'O campo "emitente" não pode conter mais que 100 caracteres!',
            'emitente.required' => 'O campo "emitente" deve ser preenchido!',
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
        $qry = ChequeEmitente::query();
        
        // Filtros
         if (!empty($filters['codchequeemitente'])) {
            $qry->where('codchequeemitente', '=', $filters['codchequeemitente']);
        }

         if (!empty($filters['codcheque'])) {
            $qry->where('codcheque', '=', $filters['codcheque']);
        }

         if (!empty($filters['cnpj'])) {
            $qry->where('cnpj', '=', $filters['cnpj']);
        }

         if (!empty($filters['emitente'])) {
            $qry->palavras('emitente', $filters['emitente']);
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
            , 'recordsTotal' => ChequeEmitente::count()
            , 'data' => $qry->get()
        ];
        
    }
    
}
