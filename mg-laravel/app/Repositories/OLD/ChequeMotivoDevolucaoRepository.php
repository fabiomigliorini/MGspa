<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\ChequeMotivoDevolucao;

/**
 * Description of ChequeMotivoDevolucaoRepository
 * 
 * @property  Validator $validator
 * @property  ChequeMotivoDevolucao $model
 */
class ChequeMotivoDevolucaoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new ChequeMotivoDevolucao();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codchequemotivodevolucao;
        }
        
        $this->validator = Validator::make($data, [
            'numero' => [
                'numeric',
                'required',
            ],
            'chequemotivodevolucao' => [
                'max:200',
                'required',
            ],
        ], [
            'numero.numeric' => 'O campo "numero" deve ser um número!',
            'numero.required' => 'O campo "numero" deve ser preenchido!',
            'chequemotivodevolucao.max' => 'O campo "chequemotivodevolucao" não pode conter mais que 200 caracteres!',
            'chequemotivodevolucao.required' => 'O campo "chequemotivodevolucao" deve ser preenchido!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->ChequeDevolucaoS->count() > 0) {
            return 'Motivo de Devolução de Cheques sendo utilizada em "ChequeDevolucao"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = ChequeMotivoDevolucao::query();
        
        // Filtros
         if (!empty($filters['codchequemotivodevolucao'])) {
            $qry->where('codchequemotivodevolucao', '=', $filters['codchequemotivodevolucao']);
        }

         if (!empty($filters['numero'])) {
            $qry->where('numero', '=', $filters['numero']);
        }

         if (!empty($filters['chequemotivodevolucao'])) {
            $qry->palavras('chequemotivodevolucao', $filters['chequemotivodevolucao']);
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
            , 'recordsTotal' => ChequeMotivoDevolucao::count()
            , 'data' => $qry->get()
        ];
        
    }
    
}
