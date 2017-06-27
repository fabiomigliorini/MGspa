<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\ChequeRepasseCheque;

/**
 * Description of ChequeRepasseChequeRepository
 * 
 * @property  Validator $validator
 * @property  ChequeRepasseCheque $model
 */
class ChequeRepasseChequeRepository extends MGRepository {
    
    public function boot() {
        $this->model = new ChequeRepasseCheque();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codchequerepassecheque;
        }
        
        $this->validator = Validator::make($data, [
            'codcheque' => [
                'numeric',
                'required',
            ],
            'codchequerepasse' => [
                'numeric',
                'required',
            ],
            'compensacao' => [
                'date',
                'nullable',
            ],
        ], [
            'codcheque.numeric' => 'O campo "codcheque" deve ser um número!',
            'codcheque.required' => 'O campo "codcheque" deve ser preenchido!',
            'codchequerepasse.numeric' => 'O campo "codchequerepasse" deve ser um número!',
            'codchequerepasse.required' => 'O campo "codchequerepasse" deve ser preenchido!',
            'compensacao.date' => 'O campo "compensacao" deve ser uma data!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->ChequeDevolucaoS->count() > 0) {
            return 'Cheque Repasse Cheque sendo utilizada em "ChequeDevolucao"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = ChequeRepasseCheque::query();
        
        // Filtros
         if (!empty($filters['codchequerepassecheque'])) {
            $qry->where('codchequerepassecheque', '=', $filters['codchequerepassecheque']);
        }

         if (!empty($filters['codcheque'])) {
            $qry->where('codcheque', '=', $filters['codcheque']);
        }

         if (!empty($filters['codchequerepasse'])) {
            $qry->where('codchequerepasse', '=', $filters['codchequerepasse']);
        }

         if (!empty($filters['criacao'])) {
            $qry->where('criacao', '=', $filters['criacao']);
        }

         if (!empty($filters['codusuariocriacao'])) {
            $qry->where('codusuariocriacao', '=', $filters['codusuariocriacao']);
        }

         if (!empty($filters['alteracao'])) {
            $qry->where('alteracao', '=', $filters['alteracao']);
        }

         if (!empty($filters['codusuarioalteracao'])) {
            $qry->where('codusuarioalteracao', '=', $filters['codusuarioalteracao']);
        }

         if (!empty($filters['compensacao'])) {
            $qry->where('compensacao', '=', $filters['compensacao']);
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
            , 'recordsTotal' => ChequeRepasseCheque::count()
            , 'data' => $qry->get()
        ];
        
    }
    
}
