<?php

namespace App\Repositories;
    
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

use App\Models\ValeCompraProdutoBarra;

/**
 * Description of ValeCompraProdutoBarraRepository
 * 
 * @property  Validator $validator
 * @property  ValeCompraProdutoBarra $model
 */
class ValeCompraProdutoBarraRepository extends MGRepository {
    
    public function boot() {
        $this->model = new ValeCompraProdutoBarra();
    }
    
    //put your code here
    public function validate($data = null, $id = null) {
        
        if (empty($data)) {
            $data = $this->modell->getAttributes();
        }
        
        if (empty($id)) {
            $id = $this->model->codvalecompraprodutobarra;
        }
        
        $this->validator = Validator::make($data, [
            'codprodutobarra' => [
                'numeric',
                'required',
            ],
            'codvalecompra' => [
                'numeric',
                'required',
            ],
            'quantidade' => [
                'numeric',
                'required',
            ],
            'preco' => [
                'digits',
                'numeric',
                'required',
            ],
            'total' => [
                'digits',
                'numeric',
                'required',
            ],
        ], [
            'codprodutobarra.numeric' => 'O campo "codprodutobarra" deve ser um número!',
            'codprodutobarra.required' => 'O campo "codprodutobarra" deve ser preenchido!',
            'codvalecompra.numeric' => 'O campo "codvalecompra" deve ser um número!',
            'codvalecompra.required' => 'O campo "codvalecompra" deve ser preenchido!',
            'quantidade.numeric' => 'O campo "quantidade" deve ser um número!',
            'quantidade.required' => 'O campo "quantidade" deve ser preenchido!',
            'preco.digits' => 'O campo "preco" deve conter no máximo 2 dígitos!',
            'preco.numeric' => 'O campo "preco" deve ser um número!',
            'preco.required' => 'O campo "preco" deve ser preenchido!',
            'total.digits' => 'O campo "total" deve conter no máximo 2 dígitos!',
            'total.numeric' => 'O campo "total" deve ser um número!',
            'total.required' => 'O campo "total" deve ser preenchido!',
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
        $qry = ValeCompraProdutoBarra::query();
        
        // Filtros
         if (!empty($filters['codvalecompraprodutobarra'])) {
            $qry->where('codvalecompraprodutobarra', '=', $filters['codvalecompraprodutobarra']);
        }

         if (!empty($filters['codprodutobarra'])) {
            $qry->where('codprodutobarra', '=', $filters['codprodutobarra']);
        }

         if (!empty($filters['codvalecompra'])) {
            $qry->where('codvalecompra', '=', $filters['codvalecompra']);
        }

         if (!empty($filters['quantidade'])) {
            $qry->where('quantidade', '=', $filters['quantidade']);
        }

         if (!empty($filters['preco'])) {
            $qry->where('preco', '=', $filters['preco']);
        }

         if (!empty($filters['total'])) {
            $qry->where('total', '=', $filters['total']);
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
        return $qry->get();
        
    }
    
}
