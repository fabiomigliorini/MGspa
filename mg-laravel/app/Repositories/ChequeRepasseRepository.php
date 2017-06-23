<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\ChequeRepasse;
use App\Repositories\ChequeRepasseChequeRepository;
use \App\Repositories\ChequeRepository;

/**
 * Description of ChequeRepasseRepository
 * 
 * @property  Validator $validator
 * @property  ChequeRepasse $model
 */
class ChequeRepasseRepository extends MGRepository {
    
    public function boot() {
        $this->model = new ChequeRepasse();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codchequerepasse;
        }
        
        $this->validator = Validator::make($data, [
            'codportador' => [
                'numeric',
                'required',
            ],
            'data' => [
                'date',
                'required',
            ],
            'observacoes' => [
                'max:',
                'nullable',
            ],
        ], [
            'codportador.numeric' => 'O campo "codportador" deve ser um número!',
            'codportador.required' => 'O campo "codportador" deve ser preenchido!',
            'data.date' => 'O campo "data" deve ser uma data!',
            'data.required' => 'O campo "data" deve ser preenchido!',
            'observacoes.max' => 'O campo "observacoes" não pode conter mais que  caracteres!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->ChequeRepasseChequeS->count() > 0) {
            return 'Cheque Repasse sendo utilizada em "ChequeRepasseCheque"!';
        }
        
        return false;
    }
    
    public function parseData ($data) {

        if(isset($data['cheques_codcheque'])){
            $codcheques = $data['cheques_codcheque'];
            $codchequerepassecheques = $data['cheques_codchequerepassecheque'];

            $data['cheques'] = [];
            foreach ($codcheques as $i => $codcheque) {
                $cheq = [
                    'codcheque' => $codcheques[$i],
                    'codchequerepassecheque' => $codchequerepassecheques[$i]
                ];
                $data['cheques'][] = $cheq;
            }
           
        }else{
            $data['cheques'][] = [];
        }
         
        return $data;
    }
    
    public function create($data = null) {
       
        if (!parent::create($data)) {
            return false;
        }
        
        return $this->salvaChequeRepasseCheque($this->model, $data['cheques']);
    
    }

    public function update($id = null, $data = null) {
        if (!parent::update($id, $data)) {
            return false;
        }
        
        return $this->salvaChequeRepasseCheque($this->model, $data['cheques']);
    }
    
    public function salvaChequeRepasseCheque($model, $codcheques) {
        
        $repoRepasCheq = new ChequeRepasseChequeRepository();
        $repoCheq = new ChequeRepository();
        
        foreach ($codcheques as $cheq) {
          
            $data = [
               'codchequerepasse'=>$model->codchequerepasse,
               'codcheque'=>$cheq['codcheque']
            ];
            
            $repoRepasCheq->create($data);
            $repoCheq->update($cheq['codcheque'],['indstatus'=>2]);
            
        }
        
        return true;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = ChequeRepasse::query();
        
        // Filtros
         if (!empty($filters['codchequerepasse'])) {
            $qry->where('codchequerepasse', '=', $filters['codchequerepasse']);
        }

         if (!empty($filters['codportador'])) {
            $qry->where('codportador', '=', $filters['codportador']);
        }

         if (!empty($filters['data'])) {
            $qry->where('data', '=', $filters['data']);
        }

         if (!empty($filters['observacoes'])) {
            $qry->palavras('observacoes', $filters['observacoes']);
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
            , 'recordsTotal' => ChequeRepasse::count()
            , 'data' => $qry->get()
        ];
        
    }
    
   
}
