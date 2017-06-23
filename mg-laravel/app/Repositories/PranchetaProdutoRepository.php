<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\PranchetaProduto;

/**
 * Description of PranchetaProdutoRepository
 * 
 * @property  Validator $validator
 * @property  PranchetaProduto $model
 */
class PranchetaProdutoRepository extends MGRepository {
    
    public function boot() {
        $this->model = new PranchetaProduto();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codpranchetaproduto;
        }
        
        $this->validator = Validator::make($data, [
            'codprancheta' => [
                'numeric',
                'required',
            ],
            'codproduto' => [
                'numeric',
                'required',
                Rule::unique('tblpranchetaproduto')->ignore($id, 'codpranchetaproduto'),
            ],
            'observacoes' => [
                'max:200',
                'nullable',
            ],
        ], [
            'codprancheta.numeric' => 'O campo "codprancheta" deve ser um número!',
            'codprancheta.required' => 'O campo "codprancheta" deve ser preenchido!',
            'codproduto.numeric' => 'O campo "codproduto" deve ser um número!',
            'codproduto.required' => 'O campo "codproduto" deve ser preenchido!',
            'codproduto.unique' => 'O produto já está vinculado à uma prancheta!',
            'observacoes.max' => 'O campo "observacoes" não pode conter mais que 200 caracteres!',
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
        $qry = PranchetaProduto::query();
        $qry->select([
            'tblpranchetaproduto.codpranchetaproduto',
            'tblpranchetaproduto.observacoes',
            'tblpranchetaproduto.criacao',
            'tblpranchetaproduto.codusuariocriacao',
            'tblpranchetaproduto.alteracao',
            'tblpranchetaproduto.codusuarioalteracao',
            'tblpranchetaproduto.codproduto',
            'tblproduto.produto',
            'tblprancheta.prancheta',
        ]);
        
        $qry->join('tblproduto', 'tblproduto.codproduto', '=', 'tblpranchetaproduto.codproduto');
        $qry->join('tblprancheta', 'tblprancheta.codprancheta', '=', 'tblpranchetaproduto.codprancheta');
        
        // Filtros
         if (!empty($filters['codpranchetaproduto'])) {
            $qry->where('tblpranchetaproduto.codpranchetaproduto', '=', $filters['codpranchetaproduto']);
        }

         if (!empty($filters['codprancheta'])) {
            $qry->where('tblpranchetaproduto.codprancheta', '=', $filters['codprancheta']);
        }

         if (!empty($filters['codproduto'])) {
            $qry->where('tblpranchetaproduto.codproduto', '=', $filters['codproduto']);
        }

         if (!empty($filters['criacao'])) {
            $qry->where('tblpranchetaproduto.criacao', '=', $filters['criacao']);
        }

         if (!empty($filters['alteracao'])) {
            $qry->where('tblpranchetaproduto.alteracao', '=', $filters['alteracao']);
        }

         if (!empty($filters['codusuariocriacao'])) {
            $qry->where('tblpranchetaproduto.codusuariocriacao', '=', $filters['codusuariocriacao']);
        }

         if (!empty($filters['codusuarioalteracao'])) {
            $qry->where('tblpranchetaproduto.codusuarioalteracao', '=', $filters['codusuarioalteracao']);
        }

          if (!empty($filters['observacoes'])) {
            $qry->palavras('tblpranchetaproduto.observacoes', $filters['observacoes']);
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
            , 'recordsTotal' => PranchetaProduto::count()
            , 'data' => $qry->get()
        ];
        
    }
    
}
