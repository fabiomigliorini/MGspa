<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\Banco;

/**
 * Description of BancoRepository
 * 
 * @property  Validator $validator
 * @property  Banco $model
 */
class BancoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new Banco();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codbanco;
        }
        
        $this->validator = Validator::make($data, [
            'banco' => [
                'max:50',
                'nullable',
            ],
            'sigla' => [
                'max:3',
                'nullable',
            ],
            'numerobanco' => [
                'numeric',
                'nullable',
            ],
        ], [
            'banco.max' => 'O campo "banco" não pode conter mais que 50 caracteres!',
            'sigla.max' => 'O campo "sigla" não pode conter mais que 3 caracteres!',
            'numerobanco.numeric' => 'O campo "numerobanco" deve ser um número!',
        ]);

        return $this->validator->passes();
        
    }
    
    public function used($id = null) {
        if (!empty($id)) {
            $this->findOrFail($id);
        }
        
        if ($this->model->ChequeS->count() > 0) {
            return 'Banco sendo utilizada em "Cheque"!';
        }
        
        if ($this->model->PortadorS->count() > 0) {
            return 'Banco sendo utilizada em "Portador"!';
        }
        
        return false;
    }
    
    public function listing($filters = [], $sort = [], $start = null, $length = null) {
        
        // Query da Entidade
        $qry = Banco::query();
        
        // Filtros
         if (!empty($filters['codbanco'])) {
            $qry->where('codbanco', '=', $filters['codbanco']);
        }

         if (!empty($filters['banco'])) {
            $qry->palavras('banco', $filters['banco']);
        }

         if (!empty($filters['sigla'])) {
            $qry->palavras('sigla', $filters['sigla']);
        }

         if (!empty($filters['numerobanco'])) {
            $qry->where('numerobanco', '=', $filters['numerobanco']);
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
            , 'recordsTotal' => Banco::count()
            , 'data' => $qry->get()
        ];
        
    }
    
    public function findByNumero($numerobanco) {
        return $this->model = Banco::where('numerobanco', '=', $numerobanco)->first();
    }
    
    public function findOrCreateByNumero ($numerobanco) {
        
        if ($model = $this->findByNumero($numerobanco)) {
            return $model;
        }
        
        if ($this->create([
            'numerobanco' => $numerobanco,
            'banco' => "$numerobanco Criado Automaticamente",
            'sigla' => $numerobanco,
        ])) {
            return $this->model;
        }
        
        return false;
    }
    
}
